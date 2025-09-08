<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // Call all your seeders here
        $this->call([
            LocationSeeder::class,
            UserSeeder::class
        ]);
    }
}
