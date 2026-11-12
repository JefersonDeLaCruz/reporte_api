<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Libro de premios de puntaje (RF-27). Cada fila es un premio ya pagado; la unicidad
 * (user_id, report_id, reason) garantiza que retirar y volver a votar, o reevaluar un
 * reporte, nunca pague el mismo premio dos veces.
 */
#[Fillable(['user_id', 'report_id', 'reason', 'points'])]
class ScoreAward extends Model
{
    public const REASON_OWNER = 'owner';
    public const REASON_CONFIRM_MATCH = 'confirm_match';
    public const REASON_RESOLVE_MATCH = 'resolve_match';

    /**
     * Paga el premio si todavía no se pagó. Devuelve true si se otorgó ahora.
     */
    public static function grant(int $userId, int $reportId, string $reason, int $points): bool
    {
        $now = now();

        $inserted = DB::table('score_awards')->insertOrIgnore([
            'user_id' => $userId,
            'report_id' => $reportId,
            'reason' => $reason,
            'points' => $points,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted === 0) {
            return false;
        }

        User::find($userId)?->addScore($points);

        return true;
    }
}
