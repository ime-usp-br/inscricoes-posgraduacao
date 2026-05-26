<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inscrição — {{ $inscricao->nome_completo }}
            </h2>
            <x-back-link
                :href="route('professor.inscricoes.index', request()->only(['q', 'periodo_id', 'disciplina_id']))"
                label="Voltar à lista"
            />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('inscricao._flash-aprovacao')

            @include('inscricao._dados-candidato', ['inscricao' => $inscricao])

            @if (auth()->user()?->isAdmin())
                @include('inscricao._aprovacao-secretaria', ['inscricao' => $inscricao, 'editavel' => false])
            @endif

            @include('inscricao._aprovacao-professor', ['inscricao' => $inscricao, 'editavel' => true])
        </div>
    </div>
</x-app-layout>
