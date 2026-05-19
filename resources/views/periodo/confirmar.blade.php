<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Confirmar Período
            </h2>
            <x-back-link :href="route('periodo.create')" label="Voltar ao formulário" />
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h1 class="text-2xl font-semibold mb-4">Confirme os dados</h1>

            <ul class="mb-4">
                <li><strong>Ano:</strong> {{ $validated['ano'] }}</li>
                <li><strong>Semestre:</strong> {{ $validated['semestre'] }}</li>
                <li><strong>Status:</strong> {{ $validated['status'] === 'aberto' ? 'Aberto' : 'Fechado' }}</li>
                <li><strong>Início das Inscrições:</strong> {{ \Carbon\Carbon::parse($validated['data_inicio_inscricao'])->format('d/m/Y') }}</li>
                <li><strong>Fim das Inscrições:</strong> {{ \Carbon\Carbon::parse($validated['data_fim_inscricao'])->format('d/m/Y') }}</li>
            </ul>

            {{-- Botão para confirmar --}}
            <form action="{{ route('periodo.salvar') }}" method="POST" class="mt-6">
                @csrf

                @foreach ($validated as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                <x-primary-button>Confirmar e Salvar</x-primary-button>
            </form>
        </div>

    </div>
</x-app-layout>
