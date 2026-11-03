<?php

namespace App\Services;

use App\Models\SolicitacaoIsencao;
use App\Models\SolicitacaoIsencaoDocumento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SolicitacaoIsencaoDocumentoService
{
    /**
     * @param  array<int, UploadedFile|null>  $arquivos
     */
    public function anexar(SolicitacaoIsencao $solicitacao, array $arquivos): void
    {
        $arquivos = array_values(array_filter(
            $arquivos,
            fn (mixed $arquivo): bool => $arquivo instanceof UploadedFile && $arquivo->isValid()
        ));

        if ($arquivos === []) {
            return;
        }

        $maxArquivos = $this->configInt('isencao.documentos.max_arquivos', 5);
        $existentes = $solicitacao->documentos()->count();

        if ($existentes + count($arquivos) > $maxArquivos) {
            throw new InvalidArgumentException("Envie no máximo {$maxArquivos} documentos.");
        }

        $maxBytes = $this->configInt('isencao.documentos.max_kb', 1024) * 1024;

        foreach ($arquivos as $arquivo) {
            if ($arquivo->getSize() > $maxBytes) {
                throw new InvalidArgumentException('Cada documento deve ter no máximo 1 MB.');
            }

            $path = $arquivo->storeAs(
                $this->diretorio($solicitacao),
                Str::uuid()->toString().'.pdf',
                $this->disco()
            );

            if (! is_string($path)) {
                throw new InvalidArgumentException('Não foi possível armazenar o documento enviado.');
            }

            $solicitacao->documentos()->create([
                'path' => $path,
                'nome_original' => $arquivo->getClientOriginalName(),
                'tamanho' => (int) $arquivo->getSize(),
                'mime' => $arquivo->getMimeType() ?: 'application/pdf',
            ]);
        }

        $solicitacao->unsetRelation('documentos');
    }

    /**
     * @param  list<int>  $ids
     */
    public function remover(SolicitacaoIsencao $solicitacao, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $documentos = $solicitacao->documentos()
            ->whereIn('id', $ids)
            ->get();

        foreach ($documentos as $documento) {
            /** @var SolicitacaoIsencaoDocumento $documento */
            $documento->delete();
        }

        $solicitacao->unsetRelation('documentos');
    }

    private function diretorio(SolicitacaoIsencao $solicitacao): string
    {
        $diretorio = config('isencao.documentos.diretorio', 'isencao');
        $base = trim(is_string($diretorio) ? $diretorio : 'isencao', '/');

        return sprintf('%s/%d/%d', $base, $solicitacao->inscricao_id, $solicitacao->id);
    }

    private function disco(): string
    {
        $disco = config('isencao.documentos.disco', 'local');

        return is_string($disco) ? $disco : 'local';
    }

    private function configInt(string $chave, int $padrao): int
    {
        $valor = config($chave, $padrao);

        return is_numeric($valor) ? (int) $valor : $padrao;
    }
}
