<?php

namespace Database\Seeders;

use App\Models\Cosmetic;
use Illuminate\Database\Seeder;

/**
 * Catálogo de la tienda. Idempotente (updateOrCreate por slug): se ejecuta desde la
 * migración de la tienda y desde DatabaseSeeder. Para agregar artículos basta con
 * sumar filas aquí y volver a correrlo; la app los pinta con `value`, sin actualizarse.
 *
 * value por tipo:
 * - frame: { colors: [hex, hex?] }  anillo del avatar (dos colores = degradado)
 * - title: { text, color }          etiqueta junto al nombre
 * - theme: { color }                tinte de la barra flotante (solo lo ve el dueño)
 *
 * Precios pensados para el ritmo de RF-27: verificar un reporte propio da 10,
 * confirmar uno verificado 2, acertar una resolución 5.
 */
class CosmeticSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Marcos de avatar
            ['marco-hoja', 'frame', 'Hoja', 'Anillo verde clásico', 30, null, ['colors' => ['#2E9E5B']]],
            ['marco-oceano', 'frame', 'Océano', 'Azul profundo', 60, null, ['colors' => ['#2F80ED', '#56CCF2']]],
            ['marco-atardecer', 'frame', 'Atardecer', 'Degradado naranja y rojo', 120, 'colaborador', ['colors' => ['#F2994A', '#E5534B']]],
            ['marco-dorado', 'frame', 'Dorado', 'Para guardianes del barrio', 250, 'guardian', ['colors' => ['#E0A100', '#F2C94C']]],
            ['marco-amatista', 'frame', 'Amatista', 'Solo para expertos', 400, 'experto', ['colors' => ['#7B4FD6', '#BB6BD9']]],

            // Títulos
            ['titulo-vecino', 'title', 'Vecino atento', 'Tu primer título', 20, null, ['text' => 'Vecino atento', 'color' => '#246A4B']],
            ['titulo-ojo', 'title', 'Ojo de águila', 'Nada se te escapa', 50, null, ['text' => 'Ojo de águila', 'color' => '#2F80ED']],
            ['titulo-guardian', 'title', 'Guardián del barrio', 'Reconocimiento a tu constancia', 150, 'guardian', ['text' => 'Guardián del barrio', 'color' => '#E0A100']],
            ['titulo-leyenda', 'title', 'Leyenda ciudadana', 'El título más alto', 500, 'experto', ['text' => 'Leyenda ciudadana', 'color' => '#7B4FD6']],

            // Color de la barra flotante
            ['tema-menta', 'theme', 'Menta', 'Barra en verde suave', 40, null, ['color' => '#CFEBDD']],
            ['tema-cielo', 'theme', 'Cielo', 'Barra en azul suave', 40, null, ['color' => '#D3E4FB']],
            ['tema-arena', 'theme', 'Arena', 'Barra en tono cálido', 80, null, ['color' => '#F6E7C8']],
            ['tema-lavanda', 'theme', 'Lavanda', 'Barra en lila', 80, 'colaborador', ['color' => '#E4DAF7']],
        ];

        foreach ($items as $sort => [$slug, $type, $name, $description, $price, $minLevel, $value]) {
            Cosmetic::updateOrCreate(['slug' => $slug], [
                'type' => $type,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'min_level' => $minLevel,
                'value' => $value,
                'sort' => $sort,
            ]);
        }

        Cosmetic::flushCatalogCache();
    }
}
