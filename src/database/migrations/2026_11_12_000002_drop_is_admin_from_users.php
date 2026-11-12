<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El estado de los reportes solo cambia por votos: se elimina el cambio manual
     * (PATCH /reports/{id}/status) y con él la columna users.is_admin, que solo servía
     * para eso. hasColumn: en instalaciones nuevas la migración de la tienda ya no la crea.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }

    public function down(): void
    {
        // No se restaura: no hay roles de administrador en el sistema.
    }
};
