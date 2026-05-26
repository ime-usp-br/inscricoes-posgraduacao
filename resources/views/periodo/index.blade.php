<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Períodos de Inscrição
            </h2>

            <div class="flex flex-wrap items-center gap-3">
                <x-back-link :href="route('secretaria')" label="Voltar à Secretaria" />
            <a href="{{ route('periodo.create') }}"
               class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                Novo período
            </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900">
                    @if ($periodos->isEmpty())
                        <p class="text-gray-700">Nenhum período cadastrado.</p>
                    @else
                        <x-data-table caption="Períodos de inscrição">
                                <thead>
                                    <tr class="text-left text-gray-600">
                                        <th class="px-4 py-3 font-semibold">Período</th>
                                        <th class="px-4 py-3 font-semibold">Início</th>
                                        <th class="px-4 py-3 font-semibold">Fim</th>
                                        <th class="px-4 py-3 font-semibold">Status</th>
                                        <th class="px-4 py-3 font-semibold text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($periodos as $p)
                                        <tr class="bg-white shadow-sm ring-1 ring-gray-200 {{ $p->status === 'aberto' ? '!bg-green-50 !ring-green-200' : '' }}">
                                            <td class="px-4 py-4 font-semibold rounded-l-lg">
                                                <a class="text-blue-700 hover:underline" href="{{ route('periodo.show', $p) }}">
                                                    {{ $p->ano }}/{{ $p->semestre }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-4">
                                                {{ optional($p->data_inicio_inscricao)->format('d/m/Y') }}
                                            </td>
                                            <td class="px-4 py-4">
                                                {{ optional($p->data_fim_inscricao)->format('d/m/Y') }}
                                            </td>
                                            <td class="px-4 py-4">
                                                @if ($p->status === 'aberto')
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">
                                                        Aberto
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800">
                                                        Fechado
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-right rounded-r-lg">
                                                <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                                    <x-table-action-edit :href="route('periodo.edit', $p)" />
                                                    @if (auth()->user()?->canDeleteSecretariaResources())
                                                        <x-table-action-delete
                                                            :action="route('periodo.destroy', $p)"
                                                            confirm="Tem certeza que deseja excluir este período?" />
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                        </x-data-table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

