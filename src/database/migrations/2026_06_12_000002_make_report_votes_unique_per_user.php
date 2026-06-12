<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-09: un usuario solo puede tener un voto activo por reporte (no uno por tipo).
     */
    public function up(): void
    {
        $duplicates = DB::table('report_votes')
            ->select('report_id', 'user_id')
            ->groupBy('report_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $votes = DB::table('report_votes')
                ->where('report_id', $duplicate->report_id)
                ->where('user_id', $duplicate->user_id)
                ->orderByDesc('created_at')
                ->get();

            foreach ($votes->slice(1) as $stale) {
                DB::table('report_votes')->where('id', $stale->id)->delete();

                $column = $stale->type === 'confirm' ? 'votes_confirm' : 'votes_resolve';
                DB::table('reports')->where('id', $stale->report_id)->decrement($column);
            }
        }

        Schema::table('report_votes', function (Blueprint $table) {
            $table->dropUnique(['report_id', 'user_id', 'type']);
            $table->unique(['report_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('report_votes', function (Blueprint $table) {
            $table->dropUnique(['report_id', 'user_id']);
            $table->unique(['report_id', 'user_id', 'type']);
        });
    }
};
