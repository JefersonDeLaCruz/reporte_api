<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar_url', 'score', 'level', 'fcm_token', 'onboarding_done'])]
#[Hidden(['password', 'remember_token', 'is_admin', 'equipped_frame', 'equipped_title', 'equipped_theme'])]
#[Appends(['cosmetics'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const LEVEL_NUEVO = 'nuevo';
    public const LEVEL_COLABORADOR = 'colaborador';
    public const LEVEL_GUARDIAN = 'guardian';
    public const LEVEL_EXPERTO = 'experto';

    /** Score mínimo para alcanzar cada nivel (RF-29). */
    public const SCORE_COLABORADOR = 20;
    public const SCORE_GUARDIAN = 100;
    public const SCORE_EXPERTO = 300;

    /** Orden de los niveles, para exigir un nivel mínimo en la tienda. */
    public const LEVEL_RANK = [
        self::LEVEL_NUEVO => 0,
        self::LEVEL_COLABORADOR => 1,
        self::LEVEL_GUARDIAN => 2,
        self::LEVEL_EXPERTO => 3,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_done' => 'boolean',
            'score' => 'integer',
            'coins' => 'integer',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Suma puntos (RF-27/RF-29). Sube `score` (define el nivel, nunca baja) y `coins`
     * (saldo de la tienda, baja al comprar), y recalcula el nivel.
     */
    public function addScore(int $points): void
    {
        $this->incrementEach(['score' => $points, 'coins' => $points]);

        $level = self::levelForScore($this->score);

        if ($level !== $this->level) {
            $this->update(['level' => $level]);
        }
    }

    /**
     * Nivel correspondiente a un score dado, según los umbrales de RF-29.
     */
    public static function levelForScore(int $score): string
    {
        return match (true) {
            $score >= self::SCORE_EXPERTO => self::LEVEL_EXPERTO,
            $score >= self::SCORE_GUARDIAN => self::LEVEL_GUARDIAN,
            $score >= self::SCORE_COLABORADOR => self::LEVEL_COLABORADOR,
            default => self::LEVEL_NUEVO,
        };
    }

    /** ¿El nivel del usuario alcanza el mínimo pedido? (null = sin requisito) */
    public function meetsLevel(?string $minLevel): bool
    {
        if ($minLevel === null) {
            return true;
        }

        return (self::LEVEL_RANK[$this->level] ?? 0) >= (self::LEVEL_RANK[$minLevel] ?? PHP_INT_MAX);
    }

    public function ownedCosmetics(): BelongsToMany
    {
        return $this->belongsToMany(Cosmetic::class, 'user_cosmetics')
            ->withPivot('price_paid')
            ->withTimestamps();
    }

    /**
     * Cosméticos equipados, resueltos contra el catálogo para que la app los pinte sin
     * conocer los slugs. Solo incluye los tipos cuyas columnas se cargaron: los listados
     * de reportes cargan marco y título (visibles para todos); el tema de la barra solo
     * viaja en /me.
     */
    protected function cosmetics(): Attribute
    {
        return Attribute::get(function () {
            $catalog = Cosmetic::catalogBySlug();
            $resolve = function (?string $slug) use ($catalog) {
                if ($slug === null || !$catalog->has($slug)) {
                    return null;
                }

                return ['slug' => $slug, 'name' => $catalog[$slug]->name] + $catalog[$slug]->value;
            };

            return [
                'frame' => $resolve($this->attributes['equipped_frame'] ?? null),
                'title' => $resolve($this->attributes['equipped_title'] ?? null),
                'theme' => $resolve($this->attributes['equipped_theme'] ?? null),
            ];
        });
    }
}
