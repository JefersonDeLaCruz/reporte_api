<?php

use Database\Seeders\CosmeticSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tienda de cosméticos (RF-27 ampliado):
     * - users.coins: saldo gastable; sube junto con score pero solo baja al comprar.
     *   El nivel sigue dependiendo de score, así que comprar no baja de rango.
     * - users.equipped_*: slug del cosmético equipado por tipo.
     * - cosmetics / user_cosmetics: catálogo y compras.
     * - score_awards: libro de premios; la unicidad (user, report, reason) impide pagar dos veces.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('coins')->default(0)->after('score');
            $table->string('equipped_frame', 64)->nullable()->after('level');
            $table->string('equipped_title', 64)->nullable()->after('equipped_frame');
            $table->string('equipped_theme', 64)->nullable()->after('equipped_title');
        });

        // Lo ganado hasta hoy queda disponible para gastar.
        DB::table('users')->update(['coins' => DB::raw('score')]);

        Schema::create('cosmetics', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->enum('type', ['frame', 'title', 'theme']);
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('price');
            $table->string('min_level', 32)->nullable();
            $table->json('value');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('user_cosmetics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cosmetic_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price_paid');
            $table->timestamps();
            $table->unique(['user_id', 'cosmetic_id']);
        });

        Schema::create('score_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 32);
            $table->integer('points');
            $table->timestamps();
            $table->unique(['user_id', 'report_id', 'reason']);
        });

        $this->backfillAwards();

        (new CosmeticSeeder())->run();
    }

    /**
     * Registra como ya pagados los premios de reportes que pasaron por verified/resolved
     * antes de esta migración, para que las reglas nuevas no los paguen otra vez.
     * No modifica score ni coins.
     */
    private function backfillAwards(): void
    {
        $now = now();
        $rows = [];

        $reports = DB::table('reports')
            ->where(fn ($q) => $q->whereNotNull('verified_at')->orWhereNotNull('resolved_at'))
            ->get(['id', 'user_id', 'verified_at', 'resolved_at']);

        foreach ($reports as $report) {
            if ($report->verified_at !== null) {
                $rows[] = [$report->user_id, $report->id, 'owner', 10];
            }

            $votes = DB::table('report_votes')->where('report_id', $report->id)->get(['user_id', 'type']);
            foreach ($votes as $vote) {
                if ($vote->type === 'confirm') {
                    $rows[] = [$vote->user_id, $report->id, 'confirm_match', 2];
                } elseif ($report->resolved_at !== null) {
                    $rows[] = [$vote->user_id, $report->id, 'resolve_match', 5];
                }
            }
        }

        foreach ($rows as [$userId, $reportId, $reason, $points]) {
            DB::table('score_awards')->insertOrIgnore([
                'user_id' => $userId,
                'report_id' => $reportId,
                'reason' => $reason,
                'points' => $points,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('score_awards');
        Schema::dropIfExists('user_cosmetics');
        Schema::dropIfExists('cosmetics');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['coins', 'equipped_frame', 'equipped_title', 'equipped_theme']);
        });
    }
};
