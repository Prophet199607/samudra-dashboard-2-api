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

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
