<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Program;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Campus::create([
            'name' => 'Bulan Campus'
        ]);

        Campus::create([
            'name' => 'Main Campus'
        ]);

        Department::create([
            'name' => 'CICT',
            'campus_id' => 1
        ]);

        Department::create([
            'name' => 'BME',
            'campus_id' => 1
        ]);

        Program::create([
            'name' => 'Bachelor of Science in Information Technology',
            'department_id' => 1
        ]);

        Program::create([
            'name' => 'Bachelor of Science in Computer Science',
            'department_id' => 1
        ]);

        Program::create([
            'name' => 'Bachelor in Technical-Vocationa Teacher Education',
            'department_id' => 1
        ]);

        Program::create([
            'name' => 'Bachelor of Science in Information Systems',
            'department_id' => 1
        ]);

        
    }
}
