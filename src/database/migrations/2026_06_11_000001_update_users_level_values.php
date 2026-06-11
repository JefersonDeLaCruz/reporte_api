<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->where('level', 'beginner')->update(['level' => 'nuevo']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('level')->default('nuevo')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('level')->default('beginner')->change();
        });

        DB::table('users')->where('level', 'nuevo')->update(['level' => 'beginner']);
    }
};
