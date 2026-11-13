<?php

namespace App\Http\Controllers\Admin;

use App\Events\ReportCreated;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Crea reportes (pines del mapa) en cualquier punto, a nombre de cualquier usuario.
 * Hace lo mismo que POST /api/reports: estado pending, evento ReportCreated y push.
 */
class ReportPinController extends Controller
{
    public function create(): View
    {
        return view('admin.report-create', [
            'categories' => Category::query()->where('active', true)->orderBy('id')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'description' => ['required', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ], [
            'latitude.required' => 'Haz clic en el mapa para elegir dónde va el pin.',
            'longitude.required' => 'Haz clic en el mapa para elegir dónde va el pin.',
        ]);

        $report = Report::create([
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'description' => $data['description'],
            'photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('reports', 'public') : null,
            'status' => 'pending',
        ]);

        ReportCreated::dispatch($report);
        (new NotificationService())->notifyNearbyUsers($report);

        Log::info('admin.create_report', [
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'ip' => $request->ip(),
        ]);

        return redirect()
            ->route('admin.reports.create')
            ->with('status', "Pin creado: reporte #{$report->id} en {$report->latitude}, {$report->longitude}.")
            ->with('last', ['lat' => (float) $report->latitude, 'lng' => (float) $report->longitude]);
    }
}
