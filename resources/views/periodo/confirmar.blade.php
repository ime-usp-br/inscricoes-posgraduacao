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
        @php
            $inicioInscricoes = \Carbon\Carbon::parse($validated['data_inicio_inscricao'], 'America/Sao_Paulo')->startOfDay();
            $fimInscricoes = \Carbon\Carbon::parse($validated['data_fim_inscricao'], 'America/Sao_Paulo')->endOfDay();
            $statusAtual = \Carbon\Carbon::now('America/Sao_Paulo')->gte($inicioInscricoes)
                && \Carbon\Carbon::now('America/Sao_Paulo')->lte($fimInscricoes);
        @endphp

        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h1 class="text-2xl font-semibold mb-4">Confirme os dados</h1>

            <ul class="mb-4">
                <li><strong>Ano:</strong> {{ $validated['ano'] }}</li>
                <li><strong>Semestre:</strong> {{ $validated['semestre'] }}</li>
                <li><strong>Início das Inscrições:</strong> {{ \Carbon\Carbon::parse($validated['data_inicio_inscricao'])->format('d/m/Y') }}</li>
                <li><strong>Fim das Inscrições:</strong> {{ \Carbon\Carbon::parse($validated['data_fim_inscricao'])->format('d/m/Y') }}</li>
                <li>
                    <strong>Status atual:</strong>
                    {{ $statusAtual ? 'Aberto' : 'Fechado' }}
                </li>
            </ul>

            <div class="mt-6 border-t border-gray-200 pt-4">
                <h2 class="text-lg font-semibold mb-2">Disciplinas do Replicado</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Serão importadas automaticamente ao confirmar (ofertas com início após
                    {{ \Carbon\Carbon::parse($validated['data_inicio_inscricao'])->format('d/m/Y') }},
                    departamentos MAT, MAC, MAE, MAP e MPM).
                </p>

                @if (count($disciplinasReplicado) === 0)
                    <p class="text-sm text-amber-700 bg-amber-50 rounded-md p-3">
                        Nenhuma disciplina encontrada no Replicado para este critério.
                        O período será criado sem disciplinas; cadastre-as manualmente depois.
                    </p>
                @else
                    <p class="text-sm text-gray-700 mb-2">
                        <strong>{{ count($disciplinasReplicado) }}</strong> disciplina(s) serão cadastradas:
                    </p>
                    <div class="overflow-x-auto max-h-64 border border-gray-200 rounded-md">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Sigla</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Departamento</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Código</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Nome</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($disciplinasReplicado as $disciplina)
                                    <tr>
                                        <td class="px-3 py-2 font-mono">{{ $disciplina['sgldis'] }}</td>
                                        <td class="px-3 py-2">{{ $disciplina['departamento'] }}</td>
                                        <td class="px-3 py-2">{{ $disciplina['codigo'] }}</td>
                                        <td class="px-3 py-2">{{ $disciplina['nome'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

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
