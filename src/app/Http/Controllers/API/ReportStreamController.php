<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportStreamController extends Controller
{
    public function changes(Request $request)
    {
        $since = $request->has('since')
            ? $request->date('since')
            : now()->subMinutes(5);
        $limit = $request->integer('limit', 50);

        $reports = Report::query()
            ->where(function ($q) use ($since) {
                $q->where('created_at', '>=', $since)
                  ->orWhere('updated_at', '>=', $since);
            })
            ->with(['category', 'user:id,name,avatar_url'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
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
