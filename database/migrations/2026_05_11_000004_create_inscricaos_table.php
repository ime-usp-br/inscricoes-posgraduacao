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
            $table->text('justificativa_disciplina_obrigatoria')->nullable();
            $table->foreignId('disciplina_opcional_1_id')->nullable()
                ->constrained('disciplinas_ofertadas')->nullOnDelete();
            $table->text('justificativa_disciplina_opcional_1')->nullable();
            $table->foreignId('disciplina_opcional_2_id')->nullable()
                ->constrained('disciplinas_ofertadas')->nullOnDelete();
            $table->text('justificativa_disciplina_opcional_2')->nullable();
            $table->string('status', 40)->default('inscrito');
            $table->string('aprovacao_obrigatoria_secretaria', 20)->nullable();
            $table->string('aprovacao_opcional_1_secretaria', 20)->nullable();
            $table->string('aprovacao_opcional_2_secretaria', 20)->nullable();
            $table->string('aprovacao_obrigatoria_professor', 20)->nullable();
            $table->string('aprovacao_opcional_1_professor', 20)->nullable();
            $table->string('aprovacao_opcional_2_professor', 20)->nullable();
            $table->json('pagamentos_disciplinas')->nullable();
            $table->string('pix_idfpix', 35)->nullable();
            $table->string('pix_status', 20)->nullable();
            $table->decimal('pix_valor', 9, 2)->nullable();
            $table->unsignedTinyInteger('pix_quantidade_disciplinas')->nullable();
            $table->text('pix_qr_code')->nullable();
            $table->timestamp('pix_gerado_em')->nullable();
            $table->timestamp('pix_pago_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscricaos');
    }
};
