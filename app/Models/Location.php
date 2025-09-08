<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'loca_code',
        'loca_name',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'location', 'loca_code');
    }
}
