<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'password' => Hash::make('12345678'),
            'role' => 'super_admin',
            'campus_id' => 1
        ]);

        User::create([
            'name' => 'Bulan Admin',
            'username' => 'bulanadmin',
            'password' => Hash::make('12345678'),
            'role' => 'campus_admin',
            'campus_id' => 2
        ]);
    }
}
