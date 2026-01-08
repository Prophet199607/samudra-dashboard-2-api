<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PreviousCollection;

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
}
