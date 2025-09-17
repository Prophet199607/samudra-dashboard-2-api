<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrderDetails extends Model
{
    use HasFactory;
    protected $connection = 'mysql';

    protected $guarded = [];

    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo(CustomerOrder::class);
    }
}
