@php
    $solicitacao = $inscricao->solicitacaoIsencao;
    $podeRevisar = $solicitacao !== null && app(\App\Services\SolicitacaoIsencaoService::class)->podeRevisar($solicitacao);
    $prazo = $inscricao->prazoPagamento();
@endphp

@if ($solicitacao)
    <section class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 lg:p-8 space-y-6 text-gray-900 dark:text-gray-100">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-semibold">Isenção da taxa</h3>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1
                        @if($solicitacao->status === \App\Enums\SolicitacaoIsencaoStatus::Pendente) bg-amber-100 text-amber-800 ring-amber-200
                        @elseif($solicitacao->status === \App\Enums\SolicitacaoIsencaoStatus::Aprovada) bg-green-100 text-green-800 ring-green-200
                        @elseif($solicitacao->status === \App\Enums\SolicitacaoIsencaoStatus::Reprovada) bg-red-100 text-red-800 ring-red-200
                        @else bg-gray-100 text-gray-700 ring-gray-200 @endif
                    ">
                        {{ $solicitacao->status->label() }}
                    </span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Solicitada em {{ $solicitacao->created_at?->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="font-medium">Disciplinas solicitadas</p>
                    <ul class="mt-2 space-y-1 text-gray-700 dark:text-gray-300">
                        @foreach ($solicitacao->disciplinas_snapshot ?? [] as $item)
                            <li>{{ $item['codigo'] ?? '—' }} — {{ $item['nome'] ?? '—' }} ({{ $item['departamento'] ?? '—' }})</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="font-medium">Documentos</p>
                    <ul class="mt-2 space-y-1">
                        @forelse ($solicitacao->documentos as $documento)
                            <li>
                                <a href="{{ route('inscricao.isencao.documento', $documento) }}"
                                   class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $documento->nome_original }}
                                </a>
                            </li>
                        @empty
                            <li class="text-gray-600 dark:text-gray-400">Nenhum documento.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div>
                <p class="text-sm font-medium">Justificativa</p>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $solicitacao->justificativa }}</p>
            </div>

            @if ($solicitacao->decidida_em)
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <p>
                        Decidida em {{ $solicitacao->decidida_em->format('d/m/Y H:i') }}
                        @if ($solicitacao->decididaPor)
                            por {{ $solicitacao->decididaPor->name }}
                        @endif
                    </p>
                    @if ($prazo)
                        <p>Prazo para pagamento: {{ $prazo->format('d/m/Y H:i') }}</p>
                    @endif
                    @if ($solicitacao->resposta_secretaria)
                        <p class="mt-2">Resposta: <span class="text-gray-800 dark:text-gray-200">{{ $solicitacao->resposta_secretaria }}</span></p>
                    @endif
                    @if ($solicitacao->motivo_reprovacao)
                        <p class="mt-2">Motivo da reprovação: <span class="text-gray-800 dark:text-gray-200">{{ $solicitacao->motivo_reprovacao }}</span></p>
                    @endif
                </div>
            @endif

            @if ($podeRevisar)
                <form method="POST" action="{{ route('inscricoes.isencao.decidir', $inscricao) }}" class="space-y-4 border-t border-gray-200 dark:border-gray-600 pt-6">
                    @csrf
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="decisao" value="aprovar" required>
                            Aprovar isenção
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="decisao" value="reprovar" required>
                            Reprovar isenção
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Resposta à secretaria (opcional)</label>
                        <textarea name="resposta_secretaria" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Motivo da reprovação (obrigatório em caso de reprovação)</label>
                        <textarea name="motivo_reprovacao" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm"></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white dark:bg-gray-200 dark:text-gray-900">
                        Salvar decisão
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400 border-t border-gray-200 dark:border-gray-600 pt-4">
                    Esta solicitação não pode mais ser revisada no momento.
                </p>
            @endif

            @if (in_array($solicitacao->status, [\App\Enums\SolicitacaoIsencaoStatus::Aprovada, \App\Enums\SolicitacaoIsencaoStatus::Reprovada], true))
                <form method="POST" action="{{ route('inscricoes.isencao.estender', $inscricao) }}" class="flex flex-wrap items-end gap-3 border-t border-gray-200 dark:border-gray-600 pt-6">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Estender prazo de pagamento</label>
                        <input type="datetime-local" name="prazo_pagamento" required class="mt-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold">
                        Estender prazo
                    </button>
                </form>
            @endif
        </div>
    </section>
@endif
