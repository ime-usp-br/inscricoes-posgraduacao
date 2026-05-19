<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricaos', function (Blueprint $table) {
            $table->string('aprovacao_obrigatoria_professor', 20)->nullable()->after('aprovacao_opcional_2_secretaria');
            $table->string('aprovacao_opcional_1_professor', 20)->nullable()->after('aprovacao_obrigatoria_professor');
            $table->string('aprovacao_opcional_2_professor', 20)->nullable()->after('aprovacao_opcional_1_professor');
        });
    }

    public function down(): void
    {
        Schema::table('inscricaos', function (Blueprint $table) {
            $table->dropColumn([
                'aprovacao_obrigatoria_professor',
                'aprovacao_opcional_1_professor',
                'aprovacao_opcional_2_professor',
            ]);
        });
    }
};
