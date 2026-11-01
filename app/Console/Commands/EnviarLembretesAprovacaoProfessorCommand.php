<?php

namespace App\Console\Commands;

use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\ProfessorPendencyReminder;
use App\Models\User;
use App\Notifications\AprovacaoProfessorPendenteNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

class EnviarLembretesAprovacaoProfessorCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'professor:enviar-lembretes-aprovacao
                            {--dry-run : Apenas exibe o que seria enviado sem disparar emails}
                            {--dias=1 : Janela mínima entre envios para o mesmo (professor, inscricao, disciplina, slot)}
                            {--force : Ignora a checagem por janela de idempotência}';

    /**
     * @var string
     */
    protected $description = 'Envia lembretes aos professores sobre inscrições pendentes de aprovação (disciplinas aprovadas pela secretaria).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $dias = max(1, (int) $this->option('dias'));
        $force = (bool) $this->option('force');

        $this->info('Buscando inscrições com disciplinas aprovadas pela secretaria...');

        $inscricoes = Inscricao::query()
            ->with([
                'disciplinaObrigatoria',
                'disciplinaOpcional1',
                'disciplinaOpcional2',
            ])
            ->comDisciplinaAprovadaPelaSecretaria()
            ->get();

        $porProfessor = [];

        foreach ($inscricoes as $inscricao) {
            $pendentes = $inscricao->disciplinasParaAprovacaoProfessor();

            if ($pendentes === []) {
                continue;
            }

            foreach ($pendentes as $item) {
                $disciplina = $item['disciplina'];
                $slot = $item['slot'];

                if ($inscricao->aprovacaoProfessorParaSlot($slot) !== null) {
                    continue;
                }

                $emailProf = DisciplinaOfertada::normalizarEmail($disciplina->professor_email);

                if ($emailProf === null) {
                    continue;
                }

                $porProfessor[$emailProf][] = [
                    'inscricao' => $inscricao,
                    'disciplina' => $disciplina,
                    'slot' => $slot,
                    'inscricao_id' => $inscricao->id,
                    'disciplina_id' => $disciplina->id,
                ];
            }
        }

        if ($porProfessor === []) {
            $this->info('Nenhuma pendência encontrada para professores.');
            return self::SUCCESS;
        }

        $gruposAvaliados = 0;
        $gruposEnviados = 0;
        $paresIgnorados = 0;
        $paresRegistrados = 0;

        $agora = now();

        foreach ($porProfessor as $emailProf => $pendencias) {
            $gruposAvaliados++;

            $pendenciasFiltradas = [];

            foreach ($pendencias as $pendencia) {
                if (! $force) {
                    $jaNotificadoRecentemente = ProfessorPendencyReminder::query()
                        ->where('professor_email', $emailProf)
                        ->where('inscricao_id', $pendencia['inscricao_id'])
                        ->where('disciplina_ofertada_id', $pendencia['disciplina_id'])
                        ->where('slot', $pendencia['slot'])
                        ->where('notified_at', '>=', $agora->copy()->subDays($dias))
                        ->exists();

                    if ($jaNotificadoRecentemente) {
                        $paresIgnorados++;
                        continue;
                    }
                }

                $pendenciasFiltradas[] = $pendencia;
            }

            if ($pendenciasFiltradas === []) {
                continue;
            }

            if ($dryRun) {
                $this->line("DRY-RUN -> {$emailProf}: ".count($pendenciasFiltradas).' pendência(s)');
                foreach ($pendenciasFiltradas as $pendencia) {
                    $disciplina = $pendencia['disciplina'];
                    $inscricao = $pendencia['inscricao'];
                    $codigo = $disciplina->codigo_completo ?? '-';
                    $this->line("  - #{$inscricao->id} | {$codigo} {$disciplina->nome} [slot: {$pendencia['slot']}]");
                }
                continue;
            }

            $user = User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$emailProf])
                ->first();

            try {
                if ($user) {
                    Notification::send($user, new AprovacaoProfessorPendenteNotification($pendenciasFiltradas));
                } else {
                    Notification::route('mail', $emailProf)
                        ->notify(new AprovacaoProfessorPendenteNotification($pendenciasFiltradas));
                }
            } catch (Throwable $e) {
                $this->error("Erro ao enviar para {$emailProf}: ".$e->getMessage());
                continue;
            }

            foreach ($pendenciasFiltradas as $pendencia) {
                try {
                    ProfessorPendencyReminder::create([
                        'professor_email' => $emailProf,
                        'user_id' => $user?->id,
                        'inscricao_id' => $pendencia['inscricao_id'],
                        'disciplina_ofertada_id' => $pendencia['disciplina_id'],
                        'slot' => $pendencia['slot'],
                        'notified_at' => $agora,
                        'meta' => [
                            'inscricao_id' => $pendencia['inscricao_id'],
                            'disciplina_id' => $pendencia['disciplina_id'],
                            'slot' => $pendencia['slot'],
                        ],
                    ]);
                    $paresRegistrados++;
                } catch (Throwable $e) {
                    $this->warn("Não foi possível registrar lembrete para {$emailProf} (inscrição #{$pendencia['inscricao_id']}): ".$e->getMessage());
                }
            }

            $gruposEnviados++;
        }

        $this->info("Grupos avaliados: {$gruposAvaliados}");
        $this->info("Grupos com e-mail enviado: {$gruposEnviados}");
        $this->info("Pares ignorados por janela recente: {$paresIgnorados}");
        $this->info("Pares registrados em professor_pendency_reminders: {$paresRegistrados}");

        return self::SUCCESS;
    }
}
