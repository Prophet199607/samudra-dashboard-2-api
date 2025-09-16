<?php

namespace App\Http\Controllers;

use App\Models\DataRepository;
use Illuminate\Http\Request;

class DataRepositoryController extends Controller
{
    // Get all customers
    public function getCustomers()
    {
        try {
            $customers = DataRepository::getAllCustomers();

            return response()->json($customers);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCustomerGroups()
    {
        try {
            $customerGroups = DataRepository::getAllCustomerGroups();

            return response()->json($customerGroups);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentTypes()
    {
        try {
            $paymentTypes = DataRepository::getAllPaymentTypes();

            return response()->json($paymentTypes);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}