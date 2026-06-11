<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar_url', 'score', 'level', 'fcm_token', 'onboarding_done'])]
#[Hidden(['password', 'remember_token'])]
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
        ];
    }

    /**
     * Suma puntos al score del usuario y recalcula su nivel (RF-27/RF-29).
     */
    public function addScore(int $points): void
    {
        $this->increment('score', $points);

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
}
