<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'remarks' => 'nullable|string',
                'status' => 'nullable|integer'
            ]);

            $order = CustomerOrder::create([
                'customer_name' => $validated['customer_name'],
                'customer_group' => $validated['customer_group'],
                'customer_branch' => $validated['customer_branch'],
                'customer_po_no' => $validated['customer_po_no'],
                'po_amount' => $validated['po_amount'],
                'orn_number' => $validated['orn_number'],
                'order_request_date' => $validated['order_request_date'],
                'remarks' => $validated['remarks'] ?? null,
                'status' => $validated['status'],
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

            // Prepare update data based on current step
            $updateData = [
                'status' => $currentStep
            ];

            // Add step-specific fields based on current step
            switch ($currentStep) {
                case 1: // Basic Order Info
                    $updateData = array_merge($updateData, [
                        'customer_name' => $request->input('customer_name'),
                        'customer_group' => $request->input('customer_group'),
                        'customer_branch' => $request->input('customer_branch'),
                        'customer_po_no' => $request->input('customer_po_no'),
                        'po_amount' => $request->input('po_amount'),
                        'order_request_date' => $request->input('order_request_date'),
                        'remarks' => $request->input('remarks'),
                    ]);
                    break;

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

                // Add cases for other steps as needed
                default:
                    // For other steps, update all fields that might be sent
                    $updateData = array_merge($updateData, [
                        'customer_name' => $request->input('customer_name'),
                        'customer_group' => $request->input('customer_group'),
                        'customer_branch' => $request->input('customer_branch'),
                        'customer_po_no' => $request->input('customer_po_no'),
                        'po_amount' => $request->input('po_amount'),
                        'order_request_date' => $request->input('order_request_date'),
                        'remarks' => $request->input('remarks'),

                        'sales_branch' => $request->input('sales_branch'),

                        'payment_type' => $request->input('payment_type'),
                        'approval_date' => $request->input('approval_date'),
                        'approval_remark' => $request->input('approval_remark'),
                        // Add other fields for subsequent steps
                    ]);
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
}