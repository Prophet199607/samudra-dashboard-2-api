<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder  extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('locations')->insert([
            [
                'loca_code' => '01',
                'loca_name' => 'SAMUDRA PUBLISHERS',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'loca_code' => '02',
                'loca_name' => 'SAMUDRA BOOK SHOP - KURUNEGALA',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'loca_code' => '03',
                'loca_name' => 'SAMUDRA BOOK SHOP - KURUNEGALA NEW',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'loca_code' => '04',
                'loca_name' => 'SAMUDRA BOOK SHOP - KANDY',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'loca_code' => '05',
                'loca_name' => 'SAMUDRA BOOK SHOP - MATARA',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'loca_code' => '06',
                'loca_name' => 'SAMUDRA BOOK SHOP - BORELLA',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'loca_code' => '07',
                'loca_name' => 'KURUNEGALA',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
