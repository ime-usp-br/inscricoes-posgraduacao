<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricaos', function (Blueprint $table) {
            $table->text('justificativa_reprovacao_secretaria')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inscricaos', function (Blueprint $table) {
            $table->dropColumn('justificativa_reprovacao_secretaria');
        });
    }
};
