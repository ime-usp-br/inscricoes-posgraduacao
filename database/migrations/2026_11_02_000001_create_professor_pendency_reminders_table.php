<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professor_pendency_reminders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('professor_email', 255)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inscricao_id')->constrained('inscricaos')->cascadeOnDelete();
            $table->foreignId('disciplina_ofertada_id')->constrained('disciplinas_ofertadas')->cascadeOnDelete();
            $table->string('slot', 20);
            $table->timestamp('notified_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(
                ['professor_email', 'inscricao_id', 'disciplina_ofertada_id', 'slot'],
                'prof_pend_rem_unique'
            );
            $table->index(['professor_email', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professor_pendency_reminders');
    }
};
