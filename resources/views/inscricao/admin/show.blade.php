<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inscrição — {{ $inscricao->nome_completo }}
            </h2>
            <a href="{{ route('inscricoes.index', request()->query()) }}" class="text-sm text-blue-700 dark:text-blue-400 hover:underline">
                Voltar à lista
            </a>
        </div>
    </x-slot>

    @php
        $rotulos = [
            'tipo' => 'Tipo de cadastro (etapa 2)',
            'unidade' => 'Unidade USP',
            'pos_graduacao_externo' => 'Pós-graduação externa à USP',
            'nome_programa_externo' => 'Nome do programa externo',
            'curso_usp_anterior' => 'Curso na USP anteriormente',
            'data_nascimento' => 'Data de nascimento',
            'genero' => 'Gênero',
            'nome_mae' => 'Nome da mãe',
            'cpf' => 'CPF',
            'rg_rne_rnm' => 'RG / RNE / RNM',
            'visto_estudante_mercosul' => 'Visto Estudante ou Mercosul (texto)',
            'orgao_expedidor' => 'Órgão expedidor',
            'estado_expedicao' => 'Estado de expedição',
            'data_expedicao' => 'Data de expedição',
            'pais_nascimento' => 'País de nascimento',
            'estado_nascimento' => 'Estado de nascimento',
            'municipio_provincia' => 'Município / Província',
            'nacionalidade' => 'Nacionalidade',
            'endereco_completo' => 'Endereço completo',
            'cep' => 'CEP',
            'telefone' => 'Telefone / Celular',
            'estrangeiro' => 'Estrangeiro',
        ];
        $pdfRotulos = [
            'pdf_comprovante_matricula' => 'Comprovante de matrícula',
            'pdf_historico_escolar' => 'Histórico escolar',
            'pdf_diploma_graduacao' => 'Diploma de graduação',
            'pdf_historico_graduacao' => 'Histórico da graduação',
            'pdf_rg_rne_rnm' => 'RG / RNM / RNE (PDF)',
            'pdf_cpf' => 'CPF (PDF)',
            'pdf_passaporte' => 'Passaporte',
            'pdf_visto_estudante_mercosul' => 'Visto Estudante ou Mercosul (PDF)',
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-green-800 dark:text-green-200">{{ session('success') }}</div>
            @endif
            @if (session('info'))
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/30 p-4 text-blue-800 dark:text-blue-200">{{ session('info') }}</div>
            @endif
            @if ($errors->has('aprovacao'))
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-red-800 dark:text-red-200">{{ $errors->first('aprovacao') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                    <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">Etapa 1</h3>
                    <dl class="grid grid-cols-1 gap-3 text-sm">
                        <div><dt class="font-medium text-gray-600 dark:text-gray-400">Nome completo</dt><dd>{{ $inscricao->nome_completo }}</dd></div>
                        <div><dt class="font-medium text-gray-600 dark:text-gray-400">E-mail</dt><dd>{{ $inscricao->email }}</dd></div>
                        <div><dt class="font-medium text-gray-600 dark:text-gray-400">Aluno USP</dt><dd>{{ $inscricao->aluno_usp ? 'Sim' : 'Não' }}</dd></div>
                        @if ($inscricao->aluno_usp && $inscricao->numero_usp)
                            <div><dt class="font-medium text-gray-600 dark:text-gray-400">Número USP</dt><dd>{{ $inscricao->numero_usp }}</dd></div>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                    <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">Etapa 2</h3>
                    @php $dados = $inscricao->dados_etapa_2; @endphp
                    @if (empty($dados) || ! is_array($dados))
                        <p class="text-sm text-gray-600 dark:text-gray-400">Nenhum dado complementar registrado.</p>
                    @else
                        <dl class="grid grid-cols-1 gap-3 text-sm">
                            @foreach ($dados as $chave => $valor)
                                @if (str_starts_with($chave, 'pdf_'))
                                    @continue
                                @endif
                                <div>
                                    <dt class="font-medium text-gray-600 dark:text-gray-400">{{ $rotulos[$chave] ?? str_replace('_', ' ', $chave) }}</dt>
                                    <dd>
                                        @if ($chave === 'tipo')
                                            @if ($valor === 'usp')
                                                Aluno USP
                                            @elseif ($valor === 'nao_usp')
                                                Não aluno USP
                                            @else
                                                {{ is_scalar($valor) ? $valor : json_encode($valor) }}
                                            @endif
                                        @elseif ($chave === 'estrangeiro')
                                            {{ $valor ? 'Sim' : 'Não' }}
                                        @elseif (in_array($chave, ['pos_graduacao_externo', 'curso_usp_anterior'], true))
                                            {{ $valor === 'sim' ? 'Sim' : ($valor === 'nao' ? 'Não' : $valor) }}
                                        @else
                                            {{ is_scalar($valor) ? $valor : json_encode($valor) }}
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-600 space-y-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Documentos (PDF)</p>
                            <ul class="list-disc pl-5 space-y-1 text-sm">
                                @foreach ($dados as $chave => $valor)
                                    @if (! str_starts_with($chave, 'pdf_') || ! is_string($valor) || $valor === '')
                                        @continue
                                    @endif
                                    <li>
                                        <a href="{{ route('inscricoes.download', [$inscricao, $chave]) }}" class="text-blue-700 dark:text-blue-400 hover:underline">
                                            {{ $pdfRotulos[$chave] ?? $chave }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                    <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">Período e disciplinas</h3>
                    <dl class="grid grid-cols-1 gap-3 text-sm">
                        <div>
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Status</dt>
                            <dd class="mt-1"><x-inscricao-status-badge :status="$inscricao->status" /></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Período</dt>
                            <dd>{{ $inscricao->periodo?->ano }}/{{ $inscricao->periodo?->semestre }}</dd>
                        </div>
                        @if ($inscricao->disciplinaObrigatoria)
                            <div>
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Disciplina obrigatória</dt>
                                <dd>{{ $inscricao->disciplinaObrigatoria->codigo_completo }} — {{ $inscricao->disciplinaObrigatoria->nome }}</dd>
                            </div>
                        @endif
                        @if ($inscricao->disciplinaOpcional1)
                            <div>
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Opcional 1</dt>
                                <dd>{{ $inscricao->disciplinaOpcional1->codigo_completo }} — {{ $inscricao->disciplinaOpcional1->nome }}</dd>
                            </div>
                        @endif
                        @if ($inscricao->disciplinaOpcional2)
                            <div>
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Opcional 2</dt>
                                <dd>{{ $inscricao->disciplinaOpcional2->codigo_completo }} — {{ $inscricao->disciplinaOpcional2->nome }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Concluída em</dt>
                            <dd>{{ optional($inscricao->concluido_em)->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if ($inscricao->etapa_concluida >= 3)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg ring-1 ring-gray-200 dark:ring-gray-700">
                    <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                        <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">
                            Aprovação pela Secretaria (1ª etapa)
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Aprove cada disciplina inscrita. O status geral passa para
                            <strong>Aprovado pela Secretaria</strong> quando todas estiverem aprovadas.
                        </p>

                        <div class="flex flex-col gap-4">
                            @foreach ($inscricao->disciplinasParaAprovacaoSecretaria() as $item)
                                @php
                                    $disciplina = $item['disciplina'];
                                    $slot = $item['slot'];
                                    $codigo = $disciplina->codigo_completo;
                                    $jaAprovada = $inscricao->aprovacaoSecretariaParaSlot($slot) === \App\Enums\AprovacaoSecretariaDisciplina::Aprovado;
                                @endphp
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 px-4 py-3">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $codigo }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $disciplina->nome }}</p>
                                    </div>
                                    @if ($jaAprovada)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1.5 text-xs font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-300 ring-1 ring-green-200 dark:ring-green-800">
                                            Aprovada pela Secretaria
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('inscricoes.aprovar-secretaria', $inscricao) }}">
                                            @csrf
                                            <input type="hidden" name="disciplina" value="{{ $slot }}">
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-green-700/30 hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                                                Aprovar {{ $codigo }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
