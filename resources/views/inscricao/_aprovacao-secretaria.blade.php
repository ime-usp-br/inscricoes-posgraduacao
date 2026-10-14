@props([
    'inscricao',
    'editavel' => true,
])

@if ($inscricao->etapa_concluida >= 3)
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
            <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">
                Aprovação pela Secretaria (1ª etapa)
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Aprove ou reprove a inscrição do aluno como um todo. A decisão da secretaria
                se aplica a todas as disciplinas inscritas.
            </p>

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

            @if ($editavel)
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <form method="POST" action="{{ route('inscricoes.aprovar-secretaria', $inscricao) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-green-700/30 hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                            Aprovar inscrição
                        </button>
                    </form>

                    <form method="POST" action="{{ route('inscricoes.reprovar-secretaria', $inscricao) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-red-700/30 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">
                            Reprovar inscrição
                        </button>
                    </form>
                </div>
            @else
                <div class="pt-2">
                    <x-inscricao-resumo-etapa-badge :resumo="$inscricao->resumoAprovacaoSecretaria()" etapa="secretaria" />
                </div>
            @endif
        </div>
    </div>
@endif