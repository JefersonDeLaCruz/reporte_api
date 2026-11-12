<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La categoría 5 pasa de "Semáforo dañado" a "Señalización vial" (semáforos,
 * señales y pintura vial). Se conserva el ID para no romper la app, que
 * envía category_id fijo (CategoryMapper.java).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->where('slug', 'semaforo-danado')
            ->update(['name' => 'Señalización vial', 'slug' => 'senalizacion-vial']);
    }

    public function down(): void
    {
        DB::table('categories')
            ->where('slug', 'senalizacion-vial')
            ->update(['name' => 'Semáforo dañado', 'slug' => 'semaforo-danado']);
    }
};
