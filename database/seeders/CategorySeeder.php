<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'Tech',
            'program_id' => 1
        ]);
        Category::create([
            'name' => 'Tech',
            'program_id' => 2
        ]);
        Category::create([
            'name' => 'Tech',
            'program_id' => 3
        ]);
        Category::create([
            'name' => 'Tech',
            'program_id' => 4
        ]);
        Category::create([
            'name' => 'Politics & Society',
            'program_id' => 5
        ]);
        Category::create([
            'name' => 'Business',
            'program_id' => 6
        ]);
        Category::create([
            'name' => 'Business',
            'program_id' => 7
        ]);
    }
}
