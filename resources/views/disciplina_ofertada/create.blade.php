<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cadastrar disciplina ofertada
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('disciplina-ofertada.store') }}" method="POST" class="space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8 text-gray-900 space-y-6">
                        @include('disciplina_ofertada._form', ['disciplina' => $disciplina, 'periodos' => $periodos])

                        <div class="flex items-center gap-3">
                            <x-primary-button>
                                Cadastrar
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

