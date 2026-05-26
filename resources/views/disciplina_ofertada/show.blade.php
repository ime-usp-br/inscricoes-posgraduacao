<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $disciplina->codigo_completo }} — {{ $disciplina->nome }}
            </h2>

            <div class="flex items-center gap-3">
                <x-table-action-edit :href="route('disciplina-ofertada.edit', $disciplina)" class="!px-4 !py-2 !text-sm">
                    Editar
                </x-table-action-edit>
                <x-back-link
                    :href="route('disciplina-ofertada.index', request()->only(['periodo_id', 'departamento', 'q']))"
                    label="Voltar à lista"
                />
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 space-y-3">
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">Período:</span>
                        {{ $disciplina->periodo?->ano }}/{{ $disciplina->periodo?->semestre }}
                    </div>
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">Departamento:</span> {{ $disciplina->departamento }}
                    </div>
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">Código:</span> {{ $disciplina->codigo }}
                    </div>
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">Professor:</span> {{ $disciplina->professor_nome ?: '—' }}
                    </div>
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">E-mail:</span> {{ $disciplina->professor_email ?: '—' }}
                    </div>
                </div>
            </div>

            @if (auth()->user()?->canDeleteSecretariaResources())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8 text-gray-900">
                        <x-table-action-delete
                            :action="route('disciplina-ofertada.destroy', $disciplina)"
                            confirm="Tem certeza que deseja excluir esta disciplina?"
                            class="!px-4 !py-2 !text-sm">
                            Excluir disciplina
                        </x-table-action-delete>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

