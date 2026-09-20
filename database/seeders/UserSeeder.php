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
            'name' => 'admin1',
            'username' => 'admin1',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'campus_id' => 1
        ]);

        User::create([
            'name' => 'admin2',
            'username' => 'admin2',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'campus_id' => 2
        ]);

        User::create([
            'name' => 'user1',
            'username' => 'user1',
            'password' => Hash::make('12345678'),
            'role' => 'user',
            'campus_id' => 1
        ]);

        User::create([
            'name' => 'user2',
            'username' => 'user2',
            'password' => Hash::make('12345678'),
            'role' => 'user',
            'campus_id' => 2
        ]);
    }
}
