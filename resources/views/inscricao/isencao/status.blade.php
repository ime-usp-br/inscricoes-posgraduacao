<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Isenção da taxa de inscrição
            </h2>
            <x-back-link :href="route('inscricao.pagamento')" label="Ir para o pagamento" />
        </div>
    </x-slot>

    @php
        $status = $solicitacao->status;
        $corStatus = match ($status) {
            \App\Enums\SolicitacaoIsencaoStatus::Pendente => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 ring-amber-200 dark:ring-amber-800',
            \App\Enums\SolicitacaoIsencaoStatus::Aprovada => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 ring-green-200 dark:ring-green-800',
            \App\Enums\SolicitacaoIsencaoStatus::Reprovada => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 ring-red-200 dark:ring-red-800',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200 ring-gray-200 dark:ring-gray-600',
        };
    @endphp

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-green-800 dark:text-green-200 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-red-800 dark:text-red-200 text-sm space-y-1">
                    @foreach ($errors->all() as $erro)
                        <p>{{ $erro }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ring-1 {{ $corStatus }}">
                            {{ $status->label() }}
                        </span>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Solicitada em {{ $solicitacao->created_at?->format('d/m/Y H:i') }}
                        </p>
                    </div>

                    @if ($status === \App\Enums\SolicitacaoIsencaoStatus::Pendente)
                        <div class="rounded-md bg-amber-50 dark:bg-amber-900/30 p-4 text-sm text-amber-800 dark:text-amber-200 space-y-1">
                            <p class="font-semibold">Solicitação em análise pela secretaria.</p>
                            <p>
                                O pagamento via Pix está indisponível enquanto a solicitação estiver pendente.
                                O prazo operacional de análise é de {{ (int) config('isencao.sla_dias_uteis', 2) }} dias úteis.
                            </p>
                        </div>
                    @elseif ($status === \App\Enums\SolicitacaoIsencaoStatus::Aprovada)
                        <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">
                            <p class="font-semibold">Isenção aprovada.</p>
                            <p class="mt-1">As disciplinas solicitadas estão isentas da taxa de inscrição.</p>
                        </div>
                    @elseif ($status === \App\Enums\SolicitacaoIsencaoStatus::Reprovada)
                        <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                            <p class="font-semibold">Isenção reprovada.</p>
                            @if ($solicitacao->motivo_reprovacao)
                                <p class="mt-1">Motivo: {{ $solicitacao->motivo_reprovacao }}</p>
                            @endif
                            <p class="mt-1">Você pode seguir com o pagamento da taxa enquanto o prazo estiver aberto.</p>
                        </div>
                    @endif

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Disciplinas solicitadas</h3>
                        <ul class="mt-2 divide-y divide-gray-200 dark:divide-gray-600 rounded-md border border-gray-200 dark:border-gray-600">
                            @foreach ($solicitacao->disciplinas_snapshot ?? [] as $item)
                                <li class="p-3 text-sm">
                                    <span class="font-medium">{{ $item['codigo'] ?? '—' }}</span>
                                    — {{ $item['nome'] ?? '—' }}
                                    <span class="block text-gray-600 dark:text-gray-400">
                                        {{ $item['departamento'] ?? '—' }}
                                        @if (isset($item['valor']))
                                            — R$ {{ number_format((float) $item['valor'], 2, ',', '.') }}
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Justificativa</h3>
                        <p class="mt-2 whitespace-pre-line text-sm">{{ $solicitacao->justificativa }}</p>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Documentos</h3>
                        @if ($solicitacao->documentos->isEmpty())
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Nenhum documento anexado.</p>
                        @else
                            <ul class="mt-2 divide-y divide-gray-200 dark:divide-gray-600 rounded-md border border-gray-200 dark:border-gray-600">
                                @foreach ($solicitacao->documentos as $documento)
                                    <li class="p-3 text-sm">
                                        <a href="{{ !empty($acessoAssinado) ? URL::temporarySignedRoute('inscricao.isencao.documento.assinado', now()->addDays((int) config('isencao.link_validade_dias', 30)), ['documento' => $documento]) : route('inscricao.isencao.documento', $documento) }}"
                                           target="_blank"
                                           rel="noopener"
                                           class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ $documento->nome_original }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    @if ($solicitacao->resposta_secretaria)
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Resposta da secretaria</h3>
                            <p class="mt-2 whitespace-pre-line text-sm">{{ $solicitacao->resposta_secretaria }}</p>
                        </div>
                    @endif

                    @if ($solicitacao->decidida_em)
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Decidida em {{ $solicitacao->decidida_em->format('d/m/Y H:i') }}
                            @if ($solicitacao->decididaPor)
                                por {{ $solicitacao->decididaPor->name }}
                            @endif.
                            @if ($inscricao->prazoPagamento() && $status !== \App\Enums\SolicitacaoIsencaoStatus::Aprovada)
                                Prazo para pagamento: {{ $inscricao->prazoPagamento()?->format('d/m/Y H:i') }}.
                            @endif
                        </p>
                    @endif

                    <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-200 dark:border-gray-600">
                        @if ($status === \App\Enums\SolicitacaoIsencaoStatus::Pendente)
                            <a href="{{ route('inscricao.isencao.edit') }}"
                               class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Editar solicitação
                            </a>
                            <form method="POST" action="{{ route('inscricao.isencao.cancelar') }}"
                                  onsubmit="return confirm('Cancelar a solicitação de isenção?');">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center rounded-md border border-red-300 dark:border-red-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30">
                                    Cancelar solicitação
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('inscricao.pagamento') }}"
                           class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            Ir para o pagamento
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
