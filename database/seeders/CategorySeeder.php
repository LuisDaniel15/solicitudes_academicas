<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Matrícula', 'Certificados', 'Becas', 'Incidencias técnicas'] as $name) {
            Category::firstOrCreate(['name' => $name], ['active' => true]);
        }
    }
}
