<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreviousCollection extends Model
{
    use HasFactory;
    protected $connection = 'mysql';

    protected $guarded = [];

    public $timestamps = false;

    public function prv_collection_details()
    {
        return $this->hasMany(PreviousCollectionDetail::class);
    }
}
