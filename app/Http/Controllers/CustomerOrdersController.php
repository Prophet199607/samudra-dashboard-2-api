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
                'customer_code' => 'nullable|string',
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
                'customer_code' => $validated['customer_code'] ?? null,
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

            // If updating step 3 and order is already past step 3, don't update status
            if ($currentStep == 3 && $order->status > 3) {
                unset($updateData['status']);
            }

            // Add step-specific fields based on current step
            switch ($currentStep) {
                case 2: // Assign Branch
                    $updateData['sales_branch'] = $request->input('sales_branch');
                    $updateData['sales_branch_code'] = $request->input('sales_branch_code');
                    break;

                case 3: // Approval Info
                    $updateData = array_merge($updateData, [
                        'payment_type' => $request->input('payment_type'),
                        'approval_date' => $request->input('approval_date'),
                        'approval_remark' => $request->input('approval_remark'),
                    ]);
                    break;

                case 6: // Payment Info
                    if ($paymentReceiptPath) {
                        $updateData['payment_receipt'] = $paymentReceiptPath;
                    }
                    break;

                case 7: // Payment Confirm
                    $paymentConfirmed = $request->input('payment_confirmed');
                    $updateData = array_merge($updateData, [
                        'payment_confirmed' => $paymentConfirmed,
                        'payment_remark' => $request->input('payment_remark'),
                    ]);

                    if ($paymentConfirmed == 0) {
                        $updateData['status'] = 5;

                        if ($order->payment_receipt) {
                            Storage::disk('public')->delete($order->payment_receipt);
                        }
                        $updateData['payment_receipt'] = null;

                        CustomerOrderDetails::where('customer_order_id', $order->id)
                            ->where('status', 6)
                            ->delete();

                        $order->update($updateData);
                        DB::commit();

                        return response()->json([
                            'message' => 'Order updated successfully',
                            'order' => $order->fresh(),
                            'success' => true
                        ], 200);
                    }
                    break;

                case 8: // Invoice Info
                    $updateData = array_merge($updateData, [
                        'invoice_no' => $request->input('invoice_no'),
                        'invoice_amount' => $request->input('invoice_amount'),
                    ]);
                    break;

                case 9: // Cash In Info
                    $updateData = array_merge($updateData, [
                        'cash_in_no' => $request->input('cash_in_no'),
                        'cash_in_amount' => $request->input('cash_in_amount'),
                        'cash_in_remark' => $request->input('cash_in_remark'),
                    ]);
                    break;

                case 10: // Delivery Info
                    $updateData = array_merge($updateData, [
                        'delivery_type' => $request->input('delivery_type'),
                        'bus_no' => $request->input('bus_no'),
                        'way_bill_no' => $request->input('way_bill_no'),
                        'tracking_no' => $request->input('tracking_no'),
                        'vehicle_no' => $request->input('vehicle_no'),
                        'driver_name' => $request->input('driver_name'),
                        'courier_name' => $request->input('courier_name'),
                        'no_of_boxes' => $request->input('no_of_boxes'),
                    ]);

                    if (isset($order->is_delayed) && $order->is_delayed == 1) {
                        $updateData['is_delayed'] = 0;
                    }

                    break;
                default:
                    $updateData = array_merge($updateData, [
                        'sales_branch' => $request->input('sales_branch'),
                        'sales_branch_code' => $request->input('sales_branch_code'),

                        'payment_type' => $request->input('payment_type'),
                        'approval_date' => $request->input('approval_date'),
                        'approval_remark' => $request->input('approval_remark'),

                        'payment_confirmed' => $request->input('payment_confirmed'),
                        'payment_remark' => $request->input('payment_remark'),

                        'invoice_no' => $request->input('invoice_no'),
                        'invoice_amount' => $request->input('invoice_amount'),

                        'cash_in_no' => $request->input('cash_in_no'),
                        'cash_in_amount' => $request->input('cash_in_amount'),
                        'cash_in_remark' => $request->input('cash_in_remark'),

                        'delivery_type' => $request->input('delivery_type'),
                        'is_delayed' => $request->input('is_delayed'),
                        'delay_reason' => $request->input('delay_reason'),
                        'bus_no' => $request->input('bus_no'),
                        'way_bill_no' => $request->input('way_bill_no'),
                        'tracking_no' => $request->input('tracking_no'),
                        'vehicle_no' => $request->input('vehicle_no'),
                        'driver_name' => $request->input('driver_name'),
                        'courier_name' => $request->input('courier_name'),
                        'no_of_boxes' => $request->input('no_of_boxes'),
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
            if (isset($updateData['status'])) {
                CustomerOrderDetails::create([
                    'customer_order_id' => $order->id,
                    'orn_number' => $order->orn_number,
                    'status' => $currentStep,
                    'changed_by' => auth()->id(),
                    'created_at' => now()
                ]);
            }

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

    public function updateDelay(Request $request, $ornNumber)
    {
        try {
            $validated = $request->validate([
                'delay_reason' => 'required|string|max:255',
            ]);

            $order = CustomerOrder::where('orn_number', $ornNumber)->firstOrFail();

            $order->update([
                'is_delayed' => 1,
                'delay_reason' => $validated['delay_reason'],
            ]);

            return response()->json([
                'message' => 'Order delay status updated successfully',
                'order' => $order->fresh(),
                'success' => true
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update order delay status: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getAllOrderDetails()
    {
        $orders = CustomerOrder::with(['order_details' => function ($query) {
            $query->with('user')->orderBy('orn_number', 'desc');
        }])->orderBy('id', 'desc')->get();

        return response()->json([
            'orders' => $orders,
            'success' => true
        ], 200);
    }

    public function getOrder($ornNumber)
    {
        $order = CustomerOrder::with(['order_details' => function ($query) {
            $query->with('user')->orderBy('orn_number', 'desc');
        }])->where('orn_number', $ornNumber)->firstOrFail();

        return response()->json([
            'order' => $order,
            'success' => true
        ], 200);
    }

    public function getDashboardStats()
    {
        $completedOrders = CustomerOrder::where('status', 10)->count();
        $totalOrders = CustomerOrder::count();
        $pendingOrders = $totalOrders - $completedOrders;

        return response()->json([
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
        ]);
    }


    public function getApprovedOrders(Request $request)
    {
        $query = CustomerOrder::where('status', 3);

        if ($request->has('location')) {
            $location = $request->input('location');
            if (!empty($location)) {
                $query->where('sales_branch_code', $location);
            }
        }

        $orders = $query->get();

        return response()->json([
            'orders' => $orders,
            'success' => true
        ], 200);
    }

    public function updateSalesOrder(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'orn_number' => 'required|string|exists:customer_orders,orn_number',
                'sales_order_no' => 'required|string',
                'sales_order_amount' => 'required|numeric',
                'sales_order_date' => 'required|date',
            ]);

            $order = CustomerOrder::where('orn_number', $validated['orn_number'])->first();

            $order->update([
                'sales_order_no' => $validated['sales_order_no'],
                'sales_order_amount' => $validated['sales_order_amount'],
                'sales_order_date' => $validated['sales_order_date'],
                'status' => 4,
            ]);

            CustomerOrderDetails::create([
                'customer_order_id' => $order->id,
                'orn_number' => $order->orn_number,
                'status' => 4,
                'changed_by' => 1,
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Sales order updated successfully',
                'order' => $order,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update sales order: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function updateQuotation(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'orn_number' => 'required|string|exists:customer_orders,orn_number',
                'quotation_no' => 'required|string',
                'quotation_amount' => 'required|numeric',
                'quotation_date' => 'required|date',
            ]);

            $order = CustomerOrder::where('orn_number', $validated['orn_number'])->first();

            $order->update([
                'quotation_no' => $validated['quotation_no'],
                'quotation_amount' => $validated['quotation_amount'],
                'quotation_date' => $validated['quotation_date'],
                'status' => 5,
            ]);

            CustomerOrderDetails::create([
                'customer_order_id' => $order->id,
                'orn_number' => $order->orn_number,
                'status' => 5,
                'changed_by' => 1,
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Quotation updated successfully',
                'order' => $order,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update quotation: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
