<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    use HasFactory;
    protected $connection = 'mysql';

    protected $guarded = [];

    public $timestamps = false;

    public function order_details()
    {
        return $this->hasMany(CustomerOrderDetails::class);
    }
}
