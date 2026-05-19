<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscricaos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->cascadeOnDelete();
            $table->string('nome_completo')->nullable();
            $table->string('email')->nullable();
            $table->boolean('aluno_usp')->nullable();
            $table->string('numero_usp', 32)->nullable();
            $table->json('dados_etapa_2')->nullable();
            $table->unsignedTinyInteger('etapa_concluida')->default(0);
            $table->timestamp('concluido_em')->nullable();
            $table->foreignId('disciplina_obrigatoria_id')->nullable()
                ->constrained('disciplinas_ofertadas')->nullOnDelete();
            $table->foreignId('disciplina_opcional_1_id')->nullable()
                ->constrained('disciplinas_ofertadas')->nullOnDelete();
            $table->foreignId('disciplina_opcional_2_id')->nullable()
                ->constrained('disciplinas_ofertadas')->nullOnDelete();
            $table->string('status', 40)->default('inscrito');
            $table->string('aprovacao_obrigatoria_secretaria', 20)->nullable();
            $table->string('aprovacao_opcional_1_secretaria', 20)->nullable();
            $table->string('aprovacao_opcional_2_secretaria', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscricaos');
    }
};
