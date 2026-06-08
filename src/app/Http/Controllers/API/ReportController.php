<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::query()
            ->with(['user:id,name,avatar_url', 'category'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }

    public function show(Report $report)
    {
        $report->load(['user:id,name,avatar_url', 'category']);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'description' => 'required|string|max:500',
                'photo' => 'nullable|image|max:5120',
            ]);

            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('reports', 'public');
            }

            $report = Report::create([
                'user_id' => $request->user()->id,
                'category_id' => $data['category_id'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'description' => $data['description'],
                'photo_path' => $photoPath,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reporte creado',
                'report' => $report->load(['user:id,name,avatar_url', 'category']),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, Report $report)
    {
        $this->authorizeOwner($request, $report);

        try {
            $data = $request->validate([
                'category_id' => 'sometimes|exists:categories,id',
                'description' => 'sometimes|string|max:500',
                'photo' => 'nullable|image|max:5120',
            ]);

            if ($request->hasFile('photo')) {
                if ($report->photo_path) {
                    Storage::disk('public')->delete($report->photo_path);
                }
                $data['photo_path'] = $request->file('photo')->store('reports', 'public');
            }

            $report->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Reporte actualizado',
                'report' => $report->fresh(['user:id,name,avatar_url', 'category']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Request $request, Report $report)
    {
        $this->authorizeOwner($request, $report);

        if ($report->photo_path) {
            Storage::disk('public')->delete($report->photo_path);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reporte eliminado',
        ]);
    }

    /**
     * Cambia el estado del reporte (ciclo de vida: pending -> verified -> resolved -> archived).
     */
    public function updateStatus(Request $request, Report $report)
    {
        try {
            $data = $request->validate([
                'status' => 'required|in:pending,verified,resolved,archived',
            ]);

            $status = $data['status'];
            $report->status = $status;
            $report->status_changed_at = now();

            $timestampField = match ($status) {
                'verified' => 'verified_at',
                'resolved' => 'resolved_at',
                'archived' => 'archived_at',
                default => null,
            };

            if ($timestampField) {
                $report->{$timestampField} = now();
            }

            $report->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado',
                'report' => $report,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    private function authorizeOwner(Request $request, Report $report): void
    {
        if ($report->user_id !== $request->user()->id) {
            abort(response()->json([
                'success' => false,
                'message' => 'No autorizado para modificar este reporte',
            ], 403));
        }
    }
}
