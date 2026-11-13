<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportStreamController extends Controller
{
    public function changes(Request $request)
    {
        // Hora del servidor tomada ANTES de consultar: el cliente la manda como `since` en la
        // siguiente llamada, así un reporte creado durante esta consulta no se pierde.
        $timestamp = now();

        // Las fechas se guardan en la zona de la app (America/El_Salvador) y la comparación
        // en SQL no convierte zonas: un `since` en UTC ("...Z", el primer sondeo de la app)
        // quedaba 6 h en el futuro y no devolvía nada. Se pasa a la zona de la app.
        $since = $request->filled('since')
            ? $request->date('since')->setTimezone(config('app.timezone'))
            : $timestamp->copy()->subMinutes(5);
        $limit = $request->integer('limit', 50);

        $reports = Report::query()
            ->where(function ($q) use ($since) {
                $q->where('created_at', '>=', $since)
                  ->orWhere('updated_at', '>=', $since);
            })
            ->with(['category', 'user:id,name,avatar_url,score,level,equipped_frame,equipped_title'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'timestamp' => $timestamp->toIso8601String(),
            'count' => $reports->count(),
            'reports' => $reports->map(fn (Report $report) => [
                'id' => $report->id,
                'latitude' => (float) $report->latitude,
                'longitude' => (float) $report->longitude,
                'status' => $report->status,
                'description' => $report->description,
                'category_id' => $report->category_id,
                'category' => $report->category,
                'user_id' => $report->user_id,
                'user' => $report->user,
                'votes_confirm' => $report->votes_confirm,
                'votes_resolve' => $report->votes_resolve,
                'photo_path' => $report->photo_path,
                'created_at' => $report->created_at->toIso8601String(),
                'updated_at' => $report->updated_at->toIso8601String(),
            ]),
        ]);
    }
}
