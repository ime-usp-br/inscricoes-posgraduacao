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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
