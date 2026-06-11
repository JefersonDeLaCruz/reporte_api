<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'category_id', 'latitude', 'longitude', 'description',
    'photo_path', 'status', 'status_changed_at', 'votes_confirm', 'votes_resolve',
    'verified_at', 'resolved_at', 'archived_at',
])]
class Report extends Model
{
    use HasFactory;

    /** Umbrales de votos para verificación automática (RF-11/RF-30). */
    public const VOTES_CONFIRM_THRESHOLD = 5;
    public const VOTES_CONFIRM_THRESHOLD_EXPERT = 3;

    /** Umbrales de votos para resolución automática (RF-12). */
    public const RESOLVE_MIN_TOTAL_VOTES = 3;
    public const RESOLVE_RATIO_THRESHOLD = 0.7;

    /** Puntos otorgados por scoring (RF-27). */
    public const SCORE_OWNER_VERIFIED = 10;
    public const SCORE_CONFIRM_MATCH = 2;
    public const SCORE_RESOLVE_MATCH = 5;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'votes_confirm' => 'integer',
            'votes_resolve' => 'integer',
            'status_changed_at' => 'datetime',
            'verified_at' => 'datetime',
            'resolved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReportVote::class);
    }

    /**
     * Distancia en metros (Haversine) entre el reporte y un punto dado.
     */
    public function distanceInMetersTo(float $latitude, float $longitude): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad((float) $this->latitude);
        $lonFrom = deg2rad((float) $this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Evalúa los umbrales de votos y aplica transiciones automáticas de
     * estado (RF-11/RF-12) junto con el scoring asociado (RF-27).
     */
    public function evaluateAutoStatus(): void
    {
        if (in_array($this->status, ['resolved', 'archived'], true)) {
            return;
        }

        // Si el reporte ya pasó por "verified", sus confirm voters ya recibieron
        // el bono RF-27; evita otorgarlo de nuevo si ahora pasa a "resolved".
        $confirmVotersAwarded = $this->verified_at !== null;

        if ($this->status === 'pending' && $this->meetsConfirmThreshold()) {
            $this->transitionTo('verified');
            $this->user?->addScore(self::SCORE_OWNER_VERIFIED);
            $this->awardVoters('confirm', self::SCORE_CONFIRM_MATCH);
            $confirmVotersAwarded = true;
        }

        if (in_array($this->status, ['pending', 'verified'], true) && $this->meetsResolveThreshold()) {
            $this->transitionTo('resolved');
            $this->awardVoters('resolve', self::SCORE_RESOLVE_MATCH);

            if (!$confirmVotersAwarded) {
                $this->awardVoters('confirm', self::SCORE_CONFIRM_MATCH);
            }
        }
    }

    private function meetsConfirmThreshold(): bool
    {
        if ($this->votes_confirm >= self::VOTES_CONFIRM_THRESHOLD) {
            return true;
        }

        return $this->votes_confirm >= self::VOTES_CONFIRM_THRESHOLD_EXPERT
            && $this->hasExpertConfirmVoter();
    }

    private function meetsResolveThreshold(): bool
    {
        $total = $this->votes_confirm + $this->votes_resolve;

        if ($total < self::RESOLVE_MIN_TOTAL_VOTES) {
            return false;
        }

        return ($this->votes_resolve / $total) >= self::RESOLVE_RATIO_THRESHOLD;
    }

    private function hasExpertConfirmVoter(): bool
    {
        return $this->votes()
            ->where('type', 'confirm')
            ->whereHas('user', fn ($query) => $query->where('level', User::LEVEL_EXPERTO))
            ->exists();
    }

    private function transitionTo(string $status): void
    {
        $timestampField = match ($status) {
            'verified' => 'verified_at',
            'resolved' => 'resolved_at',
            default => null,
        };

        $this->status = $status;
        $this->status_changed_at = now();

        if ($timestampField) {
            $this->{$timestampField} = now();
        }

        $this->save();
    }

    private function awardVoters(string $type, int $points): void
    {
        $userIds = $this->votes()->where('type', $type)->pluck('user_id');

        User::whereIn('id', $userIds)->get()->each(
            fn (User $user) => $user->addScore($points)
        );
    }
}
