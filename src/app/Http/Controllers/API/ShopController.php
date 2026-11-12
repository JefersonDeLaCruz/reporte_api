<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cosmetic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tienda de cosméticos. Se compra con users.coins (saldo gastable); el nivel depende
 * de users.score, que no baja al comprar.
 */
class ShopController extends Controller
{
    /** Columna de users donde se guarda el slug equipado, por tipo de cosmético. */
    private const EQUIP_COLUMNS = [
        'frame' => 'equipped_frame',
        'title' => 'equipped_title',
        'theme' => 'equipped_theme',
    ];

    /**
     * Catálogo con el estado de cada artículo para el usuario (comprado, equipado,
     * bloqueado por nivel) y su saldo.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $owned = $user->ownedCosmetics()->pluck('cosmetics.id')->flip();

        $items = Cosmetic::query()->orderBy('type')->orderBy('sort')->get()
            ->map(fn (Cosmetic $c) => [
                'slug' => $c->slug,
                'type' => $c->type,
                'name' => $c->name,
                'description' => $c->description,
                'price' => $c->price,
                'min_level' => $c->min_level,
                'value' => $c->value,
                'owned' => $owned->has($c->id),
                'equipped' => $user->{self::EQUIP_COLUMNS[$c->type]} === $c->slug,
                'locked' => !$user->meetsLevel($c->min_level),
            ]);

        return response()->json([
            'success' => true,
            'coins' => $user->coins,
            'score' => $user->score,
            'level' => $user->level,
            'items' => $items,
        ]);
    }

    /**
     * Compra un artículo. Bloquea la fila del usuario para que dos compras simultáneas
     * no gasten el mismo saldo.
     */
    public function purchase(Request $request, Cosmetic $cosmetic)
    {
        return DB::transaction(function () use ($request, $cosmetic) {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);

            if ($user->ownedCosmetics()->whereKey($cosmetic->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Ya tienes este artículo'], 409);
            }

            if (!$user->meetsLevel($cosmetic->min_level)) {
                return response()->json([
                    'success' => false,
                    'message' => "Necesitas nivel {$cosmetic->min_level} para comprar esto",
                ], 403);
            }

            if ($user->coins < $cosmetic->price) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes puntos suficientes',
                    'coins' => $user->coins,
                    'price' => $cosmetic->price,
                ], 422);
            }

            $user->decrement('coins', $cosmetic->price);
            $user->ownedCosmetics()->attach($cosmetic->id, ['price_paid' => $cosmetic->price]);

            return response()->json([
                'success' => true,
                'message' => 'Compra realizada',
                'coins' => $user->coins,
                'slug' => $cosmetic->slug,
            ], 201);
        });
    }

    /**
     * Equipa o quita cosméticos. Body: { frame?, title?, theme? } con un slug propio o
     * null para quitarlo. Solo se tocan los tipos enviados.
     */
    public function equip(Request $request)
    {
        try {
            $data = $request->validate([
                'frame' => 'sometimes|nullable|string|max:64',
                'title' => 'sometimes|nullable|string|max:64',
                'theme' => 'sometimes|nullable|string|max:64',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = $request->user();
        $changes = [];

        foreach (self::EQUIP_COLUMNS as $type => $column) {
            if (!array_key_exists($type, $data)) {
                continue;
            }

            $slug = $data[$type];

            if ($slug !== null) {
                $owns = $user->ownedCosmetics()
                    ->where('cosmetics.slug', $slug)
                    ->where('cosmetics.type', $type)
                    ->exists();

                if (!$owns) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes ese artículo',
                    ], 422);
                }
            }

            $changes[$column] = $slug;
        }

        $user->forceFill($changes)->save();

        return response()->json([
            'success' => true,
            'user' => $user->fresh(),
        ]);
    }
}
