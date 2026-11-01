@props([
    'inscricao',
    'editavel' => true,
])

@if ($inscricao->etapa_concluida >= 3)
    @php
        $bloqueadoAprovacao = $inscricao->temPagamentoEmAberto();
        $motivoBloqueioAprovacao = null;

        if ($bloqueadoAprovacao) {
            if ($inscricao->temIsencaoPendente()) {
                $motivoBloqueioAprovacao = 'Aprovação bloqueada: existe uma solicitação de isenção em análise pela secretaria. Aguarde a decisão antes de aprovar ou reprovar esta inscrição.';
            } else {
                $motivoBloqueioAprovacao = 'Aprovação bloqueada: há disciplinas com pagamento pendente ou aguardando isenção. É necessário que todas estejam pagas ou isentas para liberar a aprovação pela secretaria.';
            }
        }
    @endphp
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
            <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">
                Aprovação pela Secretaria (1ª etapa)
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Aprove ou reprove a inscrição do aluno como um todo. A decisão da secretaria
                se aplica a todas as disciplinas inscritas.
            </p>

            @if ($bloqueadoAprovacao && $motivoBloqueioAprovacao)
                <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-amber-800 dark:text-amber-200">{{ $motivoBloqueioAprovacao }}</p>
                    </div>
                </div>
            @endif

            <div class="flex flex-col gap-4">
                @foreach ($inscricao->disciplinasParaAprovacaoSecretaria() as $item)
                    @php
                        $disciplina = $item['disciplina'];
                        $codigo = $disciplina->codigo_completo;
                        $justificativa = $item['justificativa'] ?? null;
                    @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 px-4 py-3">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $codigo }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $disciplina->nome }}</p>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">
                            <span class="font-medium">Justificativa:</span> {{ $justificativa ?: '—' }}
                        </p>
                    </div>
                @endforeach
            </div>

            @if (filled($inscricao->justificativa_reprovacao_secretaria))
                <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">
                        Motivo da reprovação
                    </p>
                    <p class="mt-1 text-sm text-red-800 dark:text-red-200 whitespace-pre-line">{{ $inscricao->justificativa_reprovacao_secretaria }}</p>
                </div>
            @endif

            @if ($editavel)
                @php
                    $jaReprovada = $inscricao->resumoAprovacaoSecretaria() === \App\Enums\ResumoAprovacaoInscricao::Reprovada;
                @endphp

                <fieldset @disabled($bloqueadoAprovacao) class="flex flex-wrap items-center gap-3 pt-2 disabled:opacity-60">
                    <form method="POST" action="{{ route('inscricoes.aprovar-secretaria', $inscricao) }}" class="m-0">
                        @csrf
                        <button type="submit"
                                @disabled($bloqueadoAprovacao)
                                title="{{ $bloqueadoAprovacao ? $motivoBloqueioAprovacao : null }}"
                                class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-green-700/30 hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition disabled:cursor-not-allowed disabled:opacity-50">
                            Aprovar inscrição
                        </button>
                    </form>

                    <button type="button"
                            x-data=""
                            x-on:click.prevent="{{ $bloqueadoAprovacao ? '' : '$dispatch(\'open-modal\', \'reprovar-inscricao-secretaria\')' }}"
                            @disabled($bloqueadoAprovacao)
                            title="{{ $bloqueadoAprovacao ? $motivoBloqueioAprovacao : null }}"
                            @class([
                                'inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition disabled:cursor-not-allowed disabled:opacity-50',
                                'bg-red-600 text-white ring-1 ring-red-700/30 hover:bg-red-500' => ! $jaReprovada && ! $bloqueadoAprovacao,
                                'bg-red-100 text-red-800 ring-1 ring-red-200 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 dark:ring-red-800 dark:hover:bg-red-900/60' => $jaReprovada && ! $bloqueadoAprovacao,
                                'bg-gray-200 text-gray-500 ring-1 ring-gray-300 dark:bg-gray-700 dark:text-gray-400 dark:ring-gray-600' => $bloqueadoAprovacao,
                            ])>
                        {{ $jaReprovada ? 'Editar reprovação' : 'Reprovar inscrição' }}
                    </button>
                </fieldset>

                <x-modal name="reprovar-inscricao-secretaria" :show="$errors->has('justificativa')" focusable>
                    <form method="POST" action="{{ route('inscricoes.reprovar-secretaria', $inscricao) }}" class="p-6">
                        @csrf

                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ $jaReprovada ? 'Editar reprovação' : 'Reprovar inscrição' }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            @if ($jaReprovada)
                                Atualize a justificativa da reprovação desta inscrição.
                                A justificativa é opcional.
                            @else
                                Todas as disciplinas desta inscrição serão reprovadas pela secretaria.
                                A justificativa é opcional.
                            @endif
                        </p>

                        <div class="mt-4">
                            <x-input-label for="justificativa" value="Justificativa (opcional)" />
                            <textarea
                                id="justificativa"
                                name="justificativa"
                                rows="4"
                                maxlength="5000"
                                placeholder="Descreva o motivo da reprovação (opcional)."
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >{{ old('justificativa', $inscricao->justificativa_reprovacao_secretaria) }}</textarea>
                            <x-input-error :messages="$errors->get('justificativa')" class="mt-2" />
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'reprovar-inscricao-secretaria')">
                                Cancelar
                            </x-secondary-button>

                            <x-danger-button>
                                {{ $jaReprovada ? 'Salvar justificativa' : 'Confirmar reprovação' }}
                            </x-danger-button>
                        </div>
                    </form>
                </x-modal>
            @else
                <div class="pt-2">
                    <x-inscricao-resumo-etapa-badge :resumo="$inscricao->resumoAprovacaoSecretaria()" etapa="secretaria" />
                </div>
            @endif
        </div>
    </div>
@endif