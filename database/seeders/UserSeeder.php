<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'username' => 'admin1',
                'password' => Hash::make('123'),
                'location' => '01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'admin2',
                'password' => Hash::make('123'),
                'location' => '01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'Keshara',
                'password' => Hash::make('keshara'),
                'location' => '02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'Nirmal',
                'password' => Hash::make('nirmal'),
                'location' => '02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
