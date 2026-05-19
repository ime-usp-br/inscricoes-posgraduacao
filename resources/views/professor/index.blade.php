<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Professor
            </h2>
            <x-back-link :href="route('secretaria')" label="Voltar à Secretaria" />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                Avaliação final das inscrições com disciplinas já aprovadas pela secretaria.
            </p>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Inscrições
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Lista com filtros por nome, período e disciplina. Apenas inscrições com ao menos uma disciplina aprovada pela secretaria.
                    </p>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('professor.inscricoes.index') }}"
                           class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                            Ver inscrições
                        </a>
                    </div>
                </div>
            </div>

            <p class="mt-8 text-center">
                <a href="{{ route('home') }}" class="text-sm text-blue-700 dark:text-blue-400 hover:underline">
                    Ir para a página de inscrição (candidatos)
                </a>
            </p>
        </div>
    </div>
</x-app-layout>
