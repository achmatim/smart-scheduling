<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SchoolDataSeeder::class);

        User::factory()->create([
            'name' => 'Admin SMP Manggala',
            'email' => 'admin@manggala.sch.id',
            'password' => bcrypt('password'),
        ]);
    }
}
