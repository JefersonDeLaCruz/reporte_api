<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Bache', 'slug' => 'bache', 'icon' => 'pothole'],
            ['name' => 'Alumbrado público', 'slug' => 'alumbrado-publico', 'icon' => 'lightbulb'],
            ['name' => 'Basura acumulada', 'slug' => 'basura-acumulada', 'icon' => 'trash'],
            ['name' => 'Fuga de agua', 'slug' => 'fuga-de-agua', 'icon' => 'water-drop'],
            ['name' => 'Semáforo dañado', 'slug' => 'semaforo-danado', 'icon' => 'traffic-light'],
            ['name' => 'Inseguridad', 'slug' => 'inseguridad', 'icon' => 'alert-triangle'],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], $category + ['active' => true]);
        }
    }
}
