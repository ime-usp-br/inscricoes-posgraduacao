<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inscrição concluída
            </h2>
            <x-back-link :href="route('home')" label="Voltar ao início" />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100">
                    <p class="text-lg font-semibold text-green-700 dark:text-green-400">
                        Sua inscrição foi registrada com sucesso.
                    </p>
                    @if ($inscricao->pix_pago_em)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Pagamento Pix confirmado em {{ $inscricao->pix_pago_em->format('d/m/Y H:i') }}
                            @if ($inscricao->pix_valor)
                                — R$ {{ number_format((float) $inscricao->pix_valor, 2, ',', '.') }}
                            @endif
                        </p>
                    @endif
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Período: <strong>{{ $periodo->ano }}/{{ $periodo->semestre }}</strong>
                        — {{ $inscricao->nome_completo }} ({{ $inscricao->email }})
                    </p>
                    @php
                        $disciplinasSelecionadas = [
                            [
                                'rotulo' => 'Disciplina obrigatória',
                                'disciplina' => $inscricao->disciplinaObrigatoria,
                                'justificativa' => $inscricao->justificativa_disciplina_obrigatoria,
                            ],
                            [
                                'rotulo' => 'Opcional 1',
                                'disciplina' => $inscricao->disciplinaOpcional1,
                                'justificativa' => $inscricao->justificativa_disciplina_opcional_1,
                            ],
                            [
                                'rotulo' => 'Opcional 2',
                                'disciplina' => $inscricao->disciplinaOpcional2,
                                'justificativa' => $inscricao->justificativa_disciplina_opcional_2,
                            ],
                        ];
                    @endphp
                    @foreach ($disciplinasSelecionadas as $item)
                        @if ($item['disciplina'])
                            <div class="mt-4 text-sm space-y-1">
                                <p>
                                    <span class="font-medium">{{ $item['rotulo'] }}:</span>
                                    {{ $item['disciplina']->codigo_completo }} — {{ $item['disciplina']->nome }}
                                </p>
                                <p class="whitespace-pre-line text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">Justificativa:</span> {{ $item['justificativa'] ?: '—' }}
                                </p>
                            </div>
                        @endif
                    @endforeach

                    @if ($inscricao->solicitacaoIsencao)
                        <div class="mt-6 rounded-md bg-gray-50 dark:bg-gray-900/30 p-4 text-sm text-gray-700 dark:text-gray-300">
                            Solicitação de isenção:
                            <strong>{{ $inscricao->solicitacaoIsencao->status->label() }}</strong>.
                            <a href="{{ route('inscricao.isencao.show') }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                Ver detalhes
                            </a>
                        </div>
                    @elseif ($inscricao->podeSolicitarIsencao())
                        <div class="mt-6 rounded-md bg-blue-50 dark:bg-blue-900/30 p-4 text-sm text-blue-800 dark:text-blue-200">
                            <p class="font-semibold">Precisa de isenção da taxa?</p>
                            <p class="mt-1">Você ainda pode solicitar isenção das disciplinas do MAC/MAE.</p>
                            <a href="{{ route('inscricao.isencao.show') }}" class="mt-2 inline-block font-medium underline">
                                Solicitar isenção da taxa
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
