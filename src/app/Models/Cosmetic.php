<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Artículo de la tienda (marco de avatar, título o color de barra).
 * `value` es JSON y la app lo usa para pintarlo (ver CosmeticSeeder).
 */
#[Fillable(['slug', 'type', 'name', 'description', 'price', 'min_level', 'value', 'sort'])]
class Cosmetic extends Model
{
    public const TYPES = ['frame', 'title', 'theme'];

    /** Caché por petición del catálogo indexado por slug (lo usa User::cosmetics). */
    private static ?Collection $bySlug = null;

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'price' => 'integer',
        ];
    }

    public static function catalogBySlug(): Collection
    {
        return self::$bySlug ??= static::query()->get()->keyBy('slug');
    }

    public static function flushCatalogCache(): void
    {
        self::$bySlug = null;
    }
}
