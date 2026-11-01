<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_isencao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscricao_id')->unique()->constrained('inscricaos')->cascadeOnDelete();
            $table->string('status', 20)->default('pendente');
            $table->text('justificativa');
            $table->json('disciplinas_snapshot');
            $table->text('resposta_secretaria')->nullable();
            $table->text('motivo_reprovacao')->nullable();
            $table->foreignId('decidida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decidida_em')->nullable();
            $table->timestamp('prazo_pagamento_em')->nullable();
            $table->timestamp('prazo_pagamento_estendido_em')->nullable();
            $table->timestamp('lembrete_agendado_para')->nullable();
            $table->timestamp('lembrete_enviado_em')->nullable();
            $table->timestamp('cancelada_em')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('decidida_em');
            $table->index('prazo_pagamento_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_isencao');
    }
};
