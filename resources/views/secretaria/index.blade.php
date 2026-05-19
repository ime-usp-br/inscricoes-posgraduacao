<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Secretaria
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                Atalhos para cadastro e consulta de períodos, disciplinas ofertadas e inscrições.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Períodos --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Períodos de inscrição
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Cadastrar novos períodos e consultar os existentes.
                        </p>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('periodo.index') }}"
                               class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                Ver períodos
                            </a>
                            <a href="{{ route('periodo.create') }}"
                               class="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                                Novo período
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Disciplinas --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Disciplinas ofertadas
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Vinculadas a um período; listagem com filtros e cadastro.
                        </p>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('disciplina-ofertada.index') }}"
                               class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                Ver disciplinas
                            </a>
                            <a href="{{ route('disciplina-ofertada.create') }}"
                               class="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                                Nova disciplina
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Inscrições --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Inscrições
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Lista com filtros por nome, período e disciplina.
                        </p>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('inscricoes.index') }}"
                               class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                Ver inscrições
                            </a>
                        </div>
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
