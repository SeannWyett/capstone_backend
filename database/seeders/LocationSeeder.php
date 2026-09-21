<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campus;
use App\Models\College;
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

        College::create([
            'name' => 'CICT',
            'campus_id' => 1
        ]);

        College::create([
            'name' => 'BME',
            'campus_id' => 1
        ]);

        Program::create([
            'name' => 'Bachelor of Science in Information Technology',
            'college_id' => 1
        ]);

        Program::create([
            'name' => 'Bachelor of Science in Computer Science',
            'college_id' => 1
        ]);

        Program::create([
            'name' => 'Bachelor in Technical-Vocationa Teacher Education',
            'college_id' => 1
        ]);

        Program::create([
            'name' => 'Bachelor of Science in Information Systems',
            'college_id' => 1
        ]);

        Program::create([
            'name' => 'Bachelor of Public Administration',
            'college_id' => 2
        ]);

        Program::create([
            'name' => 'Bachelor of Science in Accountancy',
            'college_id' => 2
        ]);

        Program::create([
            'name' => 'Bachelor of Science in Entrepreneurship',
            'college_id' => 2
        ]);
    }
}
