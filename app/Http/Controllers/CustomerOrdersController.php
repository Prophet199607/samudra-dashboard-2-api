<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerOrder;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerOrderDetails;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomerOrdersController extends Controller
{
    public function generateOrnNumber()
    {
        $lastOrder = CustomerOrder::orderBy('id', 'desc')->first();

        if ($lastOrder && preg_match('/ORN-(\d+)/', $lastOrder->orn_number, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        $orn = 'ORN-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        return response()->json([
            'orn_number' => $orn,
            'success' => true
        ], 200);
    }

    public function createOrder(Request $request)
    {
        try {
            DB::beginTransaction();

            // Make all fields optional except basic ones
            $validated = $request->validate([
                'customer_name' => 'nullable|string',
                'customer_group' => 'nullable|string',
                'customer_branch' => 'nullable|string',
                'customer_po_no' => 'nullable|string',
                'po_amount' => 'nullable|numeric',
                'orn_number' => 'required|string|unique:customer_orders',
                'order_request_date' => 'nullable|date',
                'remark' => 'nullable|string',
                'status' => 'nullable|integer'
            ]);

            $order = CustomerOrder::create([
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_group' => $validated['customer_group'] ?? null,
                'customer_branch' => $validated['customer_branch'] ?? null,
                'customer_po_no' => $validated['customer_po_no'] ?? null,
                'po_amount' => $validated['po_amount'] ?? null,
                'orn_number' => $validated['orn_number'],
                'order_request_date' => $validated['order_request_date'] ?? null,
                'remark' => $validated['remark'] ?? null,
                'status' => $validated['status'] ?? 1,
            ]);

            // Create initial order detail for the first step only
            CustomerOrderDetails::create([
                'customer_order_id' => $order->id,
                'orn_number' => $validated['orn_number'],
                'status' => 1,
                'changed_by' => auth()->id(),
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order,
                'success' => true
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create order: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function updateOrder(Request $request, $ornNumber)
    {
        try {
            DB::beginTransaction();

            $order = CustomerOrder::where('orn_number', $ornNumber)->firstOrFail();
            $currentStep = $request->input('currentStep');

            // Handle file upload for step 6 (Payment)
            $paymentReceiptPath = null;
            if ($currentStep == 6 && $request->hasFile('payment_receipt')) {
                // Validate file
                $validator = Validator::make($request->all(), [
                    'payment_receipt' => 'required|file|mimes:jpeg,jpg,png,pdf|max:2048'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => 'File validation failed: ' . implode(', ', $validator->errors()->all()),
                        'success' => false
                    ], 422);
                }

                $file = $request->file('payment_receipt');
                $fileName = 'DS' . '-' . time() . '-' . $ornNumber . '.' . $file->getClientOriginalExtension();

                // Store file in public/payments directory
                $paymentReceiptPath = $file->storeAs('payments', $fileName, 'public');

                // Delete old file if exists
                if ($order->payment_receipt) {
                    Storage::disk('public')->delete($order->payment_receipt);
                }
            }

            // Prepare update data based on current step
            $updateData = [
                'status' => $currentStep
            ];

            // Add step-specific fields based on current step
            switch ($currentStep) {
                case 2: // Assign Branch
                    $updateData['sales_branch'] = $request->input('sales_branch');
                    break;

                case 3: // Approval Info
                    $updateData = array_merge($updateData, [
                        'payment_type' => $request->input('payment_type'),
                        'approval_date' => $request->input('approval_date'),
                        'approval_remark' => $request->input('approval_remark'),
                    ]);
                    break;

                case 4: // Sales Order Info
                    $updateData = array_merge($updateData, [
                        'sales_order_no' => $request->input('sales_order_no'),
                        'sales_order_date' => $request->input('sales_order_date'),
                    ]);
                    break;

                case 5: // Quotation Info
                    $updateData = array_merge($updateData, [
                        'quotation_no' => $request->input('quotation_no'),
                        'quotation_date' => $request->input('quotation_date'),
                    ]);
                    break;

                case 6: // Payment Info
                    if ($paymentReceiptPath) {
                        $updateData['payment_receipt'] = $paymentReceiptPath;
                    }
                    break;

                case 7: // Payment Confirm
                    $updateData = array_merge($updateData, [
                        'payment_confirmed' => $request->input('payment_confirmed'),
                        'payment_remark' => $request->input('payment_remark'),
                    ]);
                    break;

                case 8: // Delivery Info
                    $updateData = array_merge($updateData, [
                        'vehicle_no' => $request->input('vehicle_no'),
                        'driver_name' => $request->input('driver_name'),
                        'no_of_boxes' => $request->input('no_of_boxes'),
                    ]);
                    break;

                case 9: // Complete Order
                    $updateData = array_merge($updateData, [
                        'cash_in_number' => $request->input('cash_in_number'),
                        'way_bill_no' => $request->input('way_bill_no'),
                        'handover_to' => $request->input('handover_to'),
                    ]);
                    break;

                // Add cases for other steps as needed
                default:
                    // For other steps, update all fields that might be sent
                    $updateData = array_merge($updateData, [
                        'sales_branch' => $request->input('sales_branch'),

                        'payment_type' => $request->input('payment_type'),
                        'approval_date' => $request->input('approval_date'),
                        'approval_remark' => $request->input('approval_remark'),

                        'sales_order_no' => $request->input('sales_order_no'),
                        'sales_order_date' => $request->input('sales_order_date'),

                        'quotation_no' => $request->input('quotation_no'),
                        'quotation_date' => $request->input('quotation_date'),

                        'payment_confirmed' => $request->input('payment_confirmed'),
                        'payment_remark' => $request->input('payment_remark'),

                        'invoice_no' => $request->input('invoice_no'),
                        'invoice_amount' => $request->input('invoice_amount'),

                        'vehicle_no' => $request->input('vehicle_no'),
                        'driver_name' => $request->input('driver_name'),
                        'no_of_boxes' => $request->input('no_of_boxes'),

                        'cash_in_number' => $request->input('cash_in_number'),
                        'way_bill_no' => $request->input('way_bill_no'),
                        'handover_to' => $request->input('handover_to'),
                    ]);

                    // Add payment receipt if uploaded
                    if ($paymentReceiptPath) {
                        $updateData['payment_receipt'] = $paymentReceiptPath;
                    }
                    break;
            }

            // Update order with the prepared data
            $order->update($updateData);

            // Create new order detail record for the current step
            CustomerOrderDetails::create([
                'customer_order_id' => $order->id,
                'orn_number' => $order->orn_number,
                'status' => $currentStep,
                'changed_by' => auth()->id(),
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $order->fresh(),
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update order: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getAllOrderDetails()
    {
        $orders = CustomerOrder::with(['order_details' => function ($query) {
            $query->orderBy('orn_number', 'desc');
        }])->get();

        return response()->json([
            'orders' => $orders,
            'success' => true
        ], 200);
    }

    public function getOrder($ornNumber)
    {
        $order = CustomerOrder::with(['order_details' => function ($query) {
            $query->orderBy('orn_number', 'desc');
        }])->where('orn_number', $ornNumber)->firstOrFail();

        return response()->json([
            'order' => $order,
            'success' => true
        ], 200);
    }

    public function getDashboardStats()
    {
        $completedOrders = CustomerOrder::where('status', 9)->count();
        $totalOrders = CustomerOrder::count();
        $pendingOrders = $totalOrders - $completedOrders;

        return response()->json([
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
        ]);
    }
}
