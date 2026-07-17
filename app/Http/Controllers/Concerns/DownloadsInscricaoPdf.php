<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Inscricao;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait DownloadsInscricaoPdf
{
    /**
     * @var list<string>
     */
    protected array $pdfsIdentidadeOcultosAoProfessor = [
        'pdf_cpf',
        'pdf_rg_rne_rnm',
        'pdf_passaporte',
        'pdf_visto_estudante_mercosul',
    ];

    protected function streamInscricaoPdf(Inscricao $inscricao, string $campo): StreamedResponse
    {
        if (! str_starts_with($campo, 'pdf_')) {
            abort(404);
        }

        /** @var array<string, mixed>|null $dados */
        $dados = $inscricao->dados_etapa_2;
        if (! is_array($dados) || ! isset($dados[$campo]) || ! is_string($dados[$campo])) {
            abort(404);
        }

        $path = $dados[$campo];
        $prefix = 'inscricoes/'.$inscricao->id.'/';
        if (! str_starts_with($path, $prefix)) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path);
    }
}
