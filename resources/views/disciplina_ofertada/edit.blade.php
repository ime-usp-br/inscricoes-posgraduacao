<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar disciplina ofertada
            </h2>
            <x-back-link
                :href="route('disciplina-ofertada.index', request()->only(['periodo_id', 'departamento', 'q']))"
                label="Voltar à lista"
            />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('disciplina-ofertada.update', $disciplina) }}" method="POST" class="space-y-6">
                @method('PUT')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8 text-gray-900 space-y-6">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-semibold">Código completo:</span> {{ $disciplina->codigo_completo }}
                        </div>

                        @include('disciplina_ofertada._form', ['disciplina' => $disciplina, 'periodos' => $periodos])

                        <div class="flex items-center gap-3">
                            <x-primary-button>
                                Salvar alterações
                            </x-primary-button>

                            <a href="{{ route('disciplina-ofertada.index') }}" class="text-sm text-gray-700 hover:underline">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

