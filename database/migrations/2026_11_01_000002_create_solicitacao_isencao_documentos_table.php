<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacao_isencao_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_isencao_id')
                ->constrained('solicitacoes_isencao')
                ->cascadeOnDelete();
            $table->string('path');
            $table->string('nome_original');
            $table->unsignedInteger('tamanho');
            $table->string('mime', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacao_isencao_documentos');
    }
};
