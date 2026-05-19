<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Período {{ $periodo->ano }}/{{ $periodo->semestre }}
            </h2>

            <div class="flex items-center gap-3">
                <x-table-action-edit :href="route('periodo.edit', $periodo)" class="!px-4 !py-2 !text-sm">
                    Editar
                </x-table-action-edit>
                <x-back-link :href="route('periodo.index')" label="Voltar à lista" />
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900">
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($periodo->status === 'aberto')
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">
                                Aberto
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800">
                                Fechado
                            </span>
                        @endif

                        <span class="text-sm text-gray-700">
                            Inscrições: {{ optional($periodo->data_inicio_inscricao)->format('d/m/Y') }}
                            até {{ optional($periodo->data_fim_inscricao)->format('d/m/Y') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900">
                    <x-table-action-delete
                        :action="route('periodo.destroy', $periodo)"
                        confirm="Tem certeza que deseja excluir este período?"
                        class="!px-4 !py-2 !text-sm">
                        Excluir período
                    </x-table-action-delete>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

