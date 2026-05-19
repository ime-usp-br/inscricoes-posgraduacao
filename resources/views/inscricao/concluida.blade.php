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
                    @if ($inscricao->disciplinaObrigatoria)
                        <p class="mt-4 text-sm">
                            <span class="font-medium">Disciplina obrigatória:</span>
                            {{ $inscricao->disciplinaObrigatoria->codigo_completo }} — {{ $inscricao->disciplinaObrigatoria->nome }}
                        </p>
                    @endif
                    @if ($inscricao->disciplinaOpcional1)
                        <p class="mt-1 text-sm">
                            <span class="font-medium">Opcional 1:</span>
                            {{ $inscricao->disciplinaOpcional1->codigo_completo }} — {{ $inscricao->disciplinaOpcional1->nome }}
                        </p>
                    @endif
                    @if ($inscricao->disciplinaOpcional2)
                        <p class="mt-1 text-sm">
                            <span class="font-medium">Opcional 2:</span>
                            {{ $inscricao->disciplinaOpcional2->codigo_completo }} — {{ $inscricao->disciplinaOpcional2->nome }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
