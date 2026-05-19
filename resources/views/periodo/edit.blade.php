<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar Período de Inscrições
            </h2>
            <x-back-link :href="route('periodo.index')" label="Voltar à lista" />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('periodo.update', $periodo) }}" method="POST" class="mt-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8 text-gray-900">
                        <h1 class="text-2xl font-semibold text-gray-900">
                            {{ $periodo->ano }}/{{ $periodo->semestre }}
                        </h1>

                        <div class="mt-6 flex gap-4 mb-4">
                            <div class="w-1/2">
                                <label for="ano" class="block font-medium text-sm text-gray-700">Ano</label>
                                <input type="number" name="ano" id="ano"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       value="{{ old('ano', $periodo->ano) }}" required>
                                @error('ano')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="w-1/2">
                                <label for="semestre" class="block font-medium text-sm text-gray-700">Semestre</label>
                                <select name="semestre" id="semestre"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required>
                                    <option value="">Selecione</option>
                                    <option value="1" @selected(old('semestre', $periodo->semestre) == 1)>1</option>
                                    <option value="2" @selected(old('semestre', $periodo->semestre) == 2)>2</option>
                                </select>
                                @error('semestre')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="status" class="block font-medium text-sm text-gray-700">Status</label>
                            <select name="status" id="status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required>
                                <option value="">Selecione</option>
                                <option value="aberto" @selected(old('status', $periodo->status) === 'aberto')>Aberto</option>
                                <option value="fechado" @selected(old('status', $periodo->status) === 'fechado')>Fechado</option>
                            </select>
                            @error('status')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="data_inicio_inscricao" class="block font-medium text-gray-700">Início das Inscrições</label>
                            <input type="date" id="data_inicio_inscricao" name="data_inicio_inscricao"
                                   value="{{ old('data_inicio_inscricao', optional($periodo->data_inicio_inscricao)->format('Y-m-d')) }}"
                                   required
                                   class="mt-1 block w-full rounded-md shadow-sm bg-gray-100 text-gray-900 border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @error('data_inicio_inscricao')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="data_fim_inscricao" class="block font-medium text-gray-700">Fim das Inscrições</label>
                            <input type="date" id="data_fim_inscricao" name="data_fim_inscricao"
                                   value="{{ old('data_fim_inscricao', optional($periodo->data_fim_inscricao)->format('Y-m-d')) }}"
                                   required
                                   class="mt-1 block w-full rounded-md shadow-sm bg-gray-100 text-gray-900 border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @error('data_fim_inscricao')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button>
                                Salvar alterações
                            </x-primary-button>

                            <a href="{{ route('periodo.index') }}" class="text-sm text-gray-700 hover:underline">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

