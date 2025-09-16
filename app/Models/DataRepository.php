<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DataRepository extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = '';

    //  Get all customers
    public static function getAllCustomers()
    {
        return DB::connection('sqlsrv')
            ->table('Customer')
            ->select('Cust_Code', 'Cust_Name')
            ->orderBy('Cust_Name')
            ->get();
    }

    public static function getAllCustomerGroups()
    {
        return DB::connection('sqlsrv')
            ->table('tb_DiscountCustomerGroup')
            ->select('GroupCode', 'Description')
            ->orderBy('Description')
            ->get();
    }

    public static function getAllPaymentTypes()
    {
        return DB::connection('sqlsrv')
            ->table('Pos_CardTransaction')
            ->select('Card_No', 'Description')
            ->where('Active', 'T')
            ->distinct()
            ->orderBy('Card_No')
            ->get();
    }
}
