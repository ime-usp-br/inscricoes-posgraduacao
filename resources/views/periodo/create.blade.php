<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cadastrar Período de Inscrições') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- Formulário --}}
        <form action="{{ route('periodo.store') }}" method="POST" class="mt-6 space-y-6">
            @csrf
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900">

                    <h1 class="text-2xl font-semibold text-gray-900">
                        Novo Período
                    </h1>

                    <p class="mt-4 text-gray-700 leading-relaxed">
                        Preencha os campos abaixo para cadastrar o período de inscrições.
                    </p>

                    {{-- Ano/Semestre --}}
                    <div class="flex gap-4 mb-4">

                        {{-- Ano --}}
                        <div class="w-1/2">
                            <label for="ano" class="block font-medium text-sm text-gray-700">Ano</label>
                            <input type="number" name="ano" id="ano"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('ano') }}" required>
                        </div>

                        {{-- Semestre --}}
                        <div class="w-1/2">
                            <label for="semestre" class="block font-medium text-sm text-gray-700">Semestre</label>
                            <select name="semestre" id="semestre"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required>
                                <option value="">Selecione</option>
                                <option value="1" @selected(old('semestre') == 1)>1</option>
                                <option value="2" @selected(old('semestre') == 2)>2</option>
                            </select>
                        </div>

                    </div>


                        {{-- Início das Inscrições --}}
                        <div>
                            <label for="inicio" class="block font-medium text-gray-700">
                                Início das Inscrições
                            </label>

                            <input
                                type="date"
                                id="data_inicio_inscricao"
                                name="data_inicio_inscricao"
                                value="{{ old('data_inicio_inscricao', $periodo->data_inicio_inscricao ?? '') }}"
                                required
                                class="mt-1 block w-full rounded-md shadow-sm
                                       bg-gray-100 text-gray-900
                                       border-gray-300
                                       focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('data_inicio_inscricao')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Fim das inscrições --}}
                        <div>
                            <label for="fim" class="block font-medium text-gray-700">
                                Fim das Inscrições
                            </label>

                            <input
                                type="date"
                                id="data_fim_inscricao"
                                name="data_fim_inscricao"
                                value="{{ old('data_fim_inscricao', $periodo->data_fim_inscricao ?? '') }}"
                                required
                                class="mt-1 block w-full rounded-md shadow-sm
                                       bg-gray-100 text-gray-900
                                       border-gray-300
                                       focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('data_fim_inscricao')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div><br>

                        {{-- Botão --}}
                        <div>
                            <x-primary-button>
                                {{ __('Cadastrar Período') }}
                            </x-primary-button>
                        </div>
                </div>
            </div>
        </form>
        </div>
    </div>
</x-app-layout>