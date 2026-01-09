<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PreviousCollection;
use Illuminate\Support\Facades\DB;
use App\Models\PreviousCollectionDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PreviousCollectionController extends Controller
{
    public function generatePCNumber()
    {
        $lastOrder = PreviousCollection::orderBy('id', 'desc')->first();

        if ($lastOrder && preg_match('/PC-(\d+)/', $lastOrder->pc_number, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        $pc = 'PC-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        return response()->json([
            'pc_number' => $pc,
            'success' => true
        ], 200);
    }

    public function createCollection(Request $request)
    {
        try {
            DB::beginTransaction();

            // Make all fields optional except basic ones
            $validated = $request->validate([
                'customer_code' => 'required|string',
                'customer_name' => 'required|string',
                'pc_number' => 'required|string|unique:previous_collections',
                'status' => 'nullable|integer',
                'payment_receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            $receiptPath = null;
            if ($request->hasFile('payment_receipt')) {
                $file = $request->file('payment_receipt');
                $filename = 'receipt_' . time() . '_' . $file->getClientOriginalName();
                $receiptPath = $file->storeAs('collection_receipts', $filename, 'public');
            }

            $collection = PreviousCollection::create([
                'customer_code' => $validated['customer_code'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'pc_number' => $validated['pc_number'],
                'status' => 6,
                'payment_receipt' => $receiptPath,
            ]);

            // Create initial detail for Step 6
            PreviousCollectionDetail::create([
                'prv_collection_id' => $collection->id,
                'pc_number' => $validated['pc_number'],
                'status' => 6,
                'changed_by' => auth()->id(),
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Collection created successfully',
                'collection' => $collection,
                'success' => true
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create collection: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function updateCollection(Request $request, $pcNumber)
    {
        try {
            DB::beginTransaction();

            $collection = PreviousCollection::where('pc_number', $pcNumber)->firstOrFail();
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
                $fileName = 'PC-DS' . '-' . time() . '-' . $pcNumber . '.' . $file->getClientOriginalExtension();

                // Store file in public/payments directory
                $paymentReceiptPath = $file->storeAs('payments/collections', $fileName, 'public');

                // Delete old file if exists
                if ($collection->payment_receipt) {
                    Storage::disk('public')->delete($collection->payment_receipt);
                }
            }

            // Prepare update data based on current step
            $updateData = [
                'status' => $currentStep
            ];

            // Add step-specific fields based on current step
            switch ($currentStep) {
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

                        if ($collection->payment_receipt) {
                            Storage::disk('public')->delete($collection->payment_receipt);
                        }
                        $updateData['payment_receipt'] = null;

                        PreviousCollectionDetail::where('prv_collection_id', $collection->id)
                            ->where('status', 6)
                            ->delete();

                        $collection->update($updateData);
                        DB::commit();

                        return response()->json([
                            'message' => 'Collection updated successfully',
                            'collection' => $collection->fresh(),
                            'success' => true
                        ], 200);
                    }
                    break;

                case 8: // Receipt Info
                    $updateData = array_merge($updateData, [
                        'receipt_no' => $request->input('receipt_no'),
                        'receipt_amount' => $request->input('receipt_amount'),
                    ]);
                    break;

                case 9: // Cash In Info
                    $updateData = array_merge($updateData, [
                        'cash_in_no' => $request->input('cash_in_no'),
                        'cash_in_amount' => $request->input('cash_in_amount'),
                        'cash_in_remark' => $request->input('cash_in_remark'),
                    ]);
                    break;
                default:
                    $updateData = array_merge($updateData, [
                        'payment_confirmed' => $request->input('payment_confirmed'),
                        'payment_remark' => $request->input('payment_remark'),

                        'receipt_no' => $request->input('receipt_no'),
                        'receipt_amount' => $request->input('receipt_amount'),

                        'cash_in_no' => $request->input('cash_in_no'),
                        'cash_in_amount' => $request->input('cash_in_amount'),
                        'cash_in_remark' => $request->input('cash_in_remark'),
                    ]);

                    // Add payment receipt if uploaded
                    if ($paymentReceiptPath) {
                        $updateData['payment_receipt'] = $paymentReceiptPath;
                    }
                    break;
            }

            // Update collection with the prepared data
            $collection->update($updateData);

            // Create new detail record for the current step
            if (isset($updateData['status'])) {
                PreviousCollectionDetail::create([
                    'prv_collection_id' => $collection->id,
                    'pc_number' => $collection->pc_number,
                    'status' => $currentStep,
                    'changed_by' => auth()->id(),
                    'created_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Collection updated successfully',
                'collection' => $collection->fresh(),
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update collection: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function getAllCollections()
    {
        $collections = PreviousCollection::orderBy('id', 'desc')->get();

        $collectionIds = $collections->pluck('id');
        $details = PreviousCollectionDetail::with('user')
            ->whereIn('prv_collection_id', $collectionIds)
            ->orderBy('pc_number', 'desc')
            ->get()
            ->groupBy('prv_collection_id');

        foreach ($collections as $collection) {
            $collection->setRelation('prv_collection_details', $details->get($collection->id) ?? collect());
        }

        return response()->json([
            'collections' => $collections,
            'success' => true
        ], 200);
    }

    public function getCollection($pcNumber)
    {
        $collection = PreviousCollection::where('pc_number', $pcNumber)->firstOrFail();

        $details = PreviousCollectionDetail::with('user')
            ->where('prv_collection_id', $collection->id)
            ->orderBy('pc_number', 'desc')
            ->get();

        $collection->setRelation('prv_collection_details', $details);

        return response()->json([
            'collection' => $collection,
            'success' => true
        ], 200);
    }
}
