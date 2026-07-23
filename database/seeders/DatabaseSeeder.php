<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create the 3 schools
        $smp = \App\Models\School::create(['name' => 'SMP Manggala']);
        $sma = \App\Models\School::create(['name' => 'SMA Manggala']);
        $smk = \App\Models\School::create(['name' => 'SMK Manggala']);

        // 2. Create admin users for each school
        User::factory()->create([
            'name' => 'Admin SMP Manggala',
            'email' => 'admin.smp@manggala.sch.id',
            'password' => bcrypt('password'),
            'school_id' => $smp->id,
        ]);

        User::factory()->create([
            'name' => 'Admin SMA Manggala',
            'email' => 'admin.sma@manggala.sch.id',
            'password' => bcrypt('password'),
            'school_id' => $sma->id,
        ]);

        User::factory()->create([
            'name' => 'Admin SMK Manggala',
            'email' => 'admin.smk@manggala.sch.id',
            'password' => bcrypt('password'),
            'school_id' => $smk->id,
        ]);

        // 3. Call school-specific data seeder
        $this->call(SchoolDataSeeder::class);
    }
}
