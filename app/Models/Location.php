<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Location';
    protected $primaryKey = 'Id_No';
    public $timestamps = false;

    protected $fillable = [
        'Id_No',
        'Loca',
        'Loca_Descrip',
        'Show',
        'Loca_Type',
        'Loca_Descrip_Short'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'location', 'Loca');
    }
}
