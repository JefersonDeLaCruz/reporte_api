<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Genera reportes de prueba ubicados en San Miguel, El Salvador
     * (centro aprox. 13.4833, -88.1833), usando ReportFactory.
     */
    public function run(): void
    {
        $users = User::factory(5)->create();
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $categories = Category::factory(4)->create();
        }

        Report::factory(15)
            ->recycle($users)
            ->recycle($categories)
            ->create();

        Report::factory(5)
            ->verified()
            ->recycle($users)
            ->recycle($categories)
            ->create();

        Report::factory(3)
            ->resolved()
            ->recycle($users)
            ->recycle($categories)
            ->create();
    }
}
