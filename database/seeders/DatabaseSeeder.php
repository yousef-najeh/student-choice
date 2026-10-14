<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Creates the one admin account the app needs
     * (there is no public admin-signup route by design). Real catalog data — majors,
     * institutions, scholarships, etc. — comes from `php artisan import:majors`.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@student-choice.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'admin',
            ]
        );
    }
}
