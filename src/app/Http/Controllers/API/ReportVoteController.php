<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportVote;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportVoteController extends Controller
{
    private const MAX_DISTANCE_METERS = 500;

    /**
     * Registra un voto (confirm|resolve) si el usuario está dentro de 500m del reporte.
     * Bloqueo optimista: la unicidad (report_id, user_id, type) en DB evita votos duplicados
     * ante condiciones de carrera; si choca, se reporta "ya votado".
     */
    public function store(Request $request, Report $report)
    {
        try {
            $data = $request->validate([
                'type' => 'required|in:confirm,resolve',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);

            $distance = $report->distanceInMetersTo((float) $data['latitude'], (float) $data['longitude']);

            if ($distance > self::MAX_DISTANCE_METERS) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes estar a menos de 500m del reporte para votar',
                    'distance_meters' => round($distance, 1),
                ], 422);
            }

            $column = $data['type'] === 'confirm' ? 'votes_confirm' : 'votes_resolve';

            DB::transaction(function () use ($report, $request, $data, $column) {
                ReportVote::create([
                    'report_id' => $report->id,
                    'user_id' => $request->user()->id,
                    'type' => $data['type'],
                ]);

                $report->increment($column);
            });

            return response()->json([
                'success' => true,
                'message' => 'Voto registrado',
                'report' => $report->fresh(),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya votaste este reporte con ese tipo',
                ], 409);
            }

            throw $e;
        }
    }

    public function destroy(Request $request, Report $report, string $type)
    {
        $vote = ReportVote::where('report_id', $report->id)
            ->where('user_id', $request->user()->id)
            ->where('type', $type)
            ->first();

        if (!$vote) {
            return response()->json([
                'success' => false,
                'message' => 'No has votado este reporte con ese tipo',
            ], 404);
        }

        $column = $type === 'confirm' ? 'votes_confirm' : 'votes_resolve';

        DB::transaction(function () use ($vote, $report, $column) {
            $vote->delete();
            $report->decrement($column);
        });

        return response()->json([
            'success' => true,
            'message' => 'Voto retirado',
            'report' => $report->fresh(),
        ]);
    }
}
