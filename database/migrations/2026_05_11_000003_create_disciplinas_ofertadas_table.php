<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinas_ofertadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->string('departamento', 3); // MAT, MAC, MAP, MAE
            $table->string('codigo', 4); // 4 dígitos
            $table->string('nome');
            $table->string('professor_nome');
            $table->string('professor_email');
            $table->timestamps();

            $table->index(['departamento', 'codigo']);
            $table->unique(['periodo_id', 'departamento', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinas_ofertadas');
    }
};

