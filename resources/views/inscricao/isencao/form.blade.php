<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Isenção da taxa de inscrição
            </h2>
            <x-back-link :href="route('inscricao.pagamento')" label="Voltar ao pagamento" />
        </div>
    </x-slot>

    @php
        $slotsMarcados = old(
            'disciplinas',
            $solicitacao
                ? array_column($solicitacao->disciplinas_snapshot ?? [], 'slot')
                : array_column($disciplinas, 'slot')
        );
        $documentosExistentes = $solicitacao?->documentos ?? collect();
        $removerSelecionados = array_map('intval', (array) old('remover_documentos', []));
    @endphp

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-red-800 dark:text-red-200 text-sm space-y-1">
                    @foreach ($errors->all() as $erro)
                        <p>{{ $erro }}</p>
                    @endforeach
                </div>
            @endif

            @if ($solicitacao?->status === \App\Enums\SolicitacaoIsencaoStatus::Cancelada)
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/30 p-4 text-sm text-blue-800 dark:text-blue-200">
                    Sua solicitação anterior foi cancelada. Você pode enviar uma nova solicitação enquanto o período de inscrições estiver aberto.
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-6">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Período: <strong>{{ $periodo?->ano }}/{{ $periodo?->semestre }}</strong>
                            — {{ $inscricao->nome_completo }} ({{ $inscricao->email }})
                            @if ($inscricao->numero_usp)
                                — NUSP {{ $inscricao->numero_usp }}
                            @endif
                        </p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            A isenção vale para disciplinas do MAC/MAE que exigem taxa. A secretaria analisa a solicitação
                            como um todo, não disciplina por disciplina. Enquanto a análise estiver pendente, o pagamento via Pix
                            ficará indisponível.
                        </p>
                    </div>

                    <form method="POST"
                          action="{{ $solicitacao ? route('inscricao.isencao.update') : route('inscricao.isencao.store') }}"
                          enctype="multipart/form-data"
                          class="space-y-6">
                        @csrf
                        @if ($solicitacao)
                            @method('PUT')
                        @endif

                        <fieldset>
                            <legend class="text-sm font-medium">Disciplinas com taxa</legend>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Marque as disciplinas para as quais deseja solicitar isenção. As disciplinas elegíveis já vêm selecionadas.
                            </p>
                            <div class="mt-3 space-y-2">
                                @foreach ($disciplinas as $item)
                                    <label class="flex items-start gap-3 rounded-md border border-gray-200 dark:border-gray-600 p-3">
                                        <input type="checkbox"
                                               name="disciplinas[]"
                                               value="{{ $item['slot'] }}"
                                               class="mt-1 rounded border-gray-300 dark:border-gray-600"
                                               @checked(in_array($item['slot'], $slotsMarcados, true)) />
                                        <span class="text-sm">
                                            <span class="font-medium">{{ $item['disciplina']->codigo_completo }}</span>
                                            — {{ $item['disciplina']->nome }}
                                            <span class="block text-gray-600 dark:text-gray-400">
                                                {{ $item['disciplina']->departamento }} — taxa de R$ {{ number_format((float) config('wspix.valor_por_disciplina', 107), 2, ',', '.') }}
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('disciplinas')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </fieldset>

                        <div>
                            <label for="justificativa" class="block text-sm font-medium">Justificativa</label>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Descreva sua situação socioeconômica e por que não consegue arcar com a taxa.
                                Mínimo de {{ (int) config('isencao.justificativa.min', 20) }} caracteres.
                            </p>
                            <textarea id="justificativa"
                                      name="justificativa"
                                      rows="6"
                                      required
                                      class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">{{ old('justificativa', $solicitacao->justificativa ?? '') }}</textarea>
                            @error('justificativa')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <p class="text-sm font-medium">Documentos comprobatórios (PDF)</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Anexe de 1 a {{ (int) config('isencao.documentos.max_arquivos', 5) }} arquivos em PDF,
                                com no máximo {{ (int) config('isencao.documentos.max_kb', 1024) / 1024 }} MB cada.
                            </p>

                            @if ($documentosExistentes->isNotEmpty())
                                <ul class="mt-3 divide-y divide-gray-200 dark:divide-gray-600 rounded-md border border-gray-200 dark:border-gray-600">
                                    @foreach ($documentosExistentes as $documento)
                                        <li class="flex flex-wrap items-center justify-between gap-2 p-3 text-sm">
                                            <a href="{{ !empty($acessoAssinado) ? URL::temporarySignedRoute('inscricao.isencao.documento.assinado', now()->addDays((int) config('isencao.link_validade_dias', 30)), ['documento' => $documento]) : route('inscricao.isencao.documento', $documento) }}"
                                               target="_blank"
                                               rel="noopener"
                                               class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $documento->nome_original }}
                                            </a>
                                            <label class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                                <input type="checkbox"
                                                       name="remover_documentos[]"
                                                       value="{{ $documento->id }}"
                                                       class="rounded border-gray-300 dark:border-gray-600"
                                                       @checked(in_array($documento->id, $removerSelecionados, true)) />
                                                Remover
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <input type="file"
                                   name="documentos[]"
                                   accept="application/pdf,.pdf"
                                   multiple
                                   class="mt-3 block w-full text-sm text-gray-700 dark:text-gray-300" />
                            @error('documentos')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            @error('documentos.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <x-primary-button>
                                {{ $solicitacao?->status === \App\Enums\SolicitacaoIsencaoStatus::Cancelada ? 'Reabrir solicitação' : 'Enviar solicitação' }}
                            </x-primary-button>
                            <a href="{{ route('inscricao.pagamento') }}"
                               class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                                Voltar sem solicitar isenção
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
