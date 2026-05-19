<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inscrições — Aprovação do Professor
            </h2>
            <x-back-link :href="route('professor')" label="Voltar ao Professor" />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Exibindo inscrições concluídas com ao menos uma disciplina aprovada pela secretaria.
                    </p>

                    <form method="GET" action="{{ route('professor.inscricoes.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Busca por nome</label>
                            <input type="search" name="q" value="{{ $search }}" placeholder="Nome completo..."
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Período</label>
                            <select name="periodo_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm">
                                <option value="">Todos</option>
                                @foreach ($periodos as $p)
                                    <option value="{{ $p->id }}" @selected((int) $periodoId === (int) $p->id)>
                                        {{ $p->ano }}/{{ $p->semestre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Disciplina</label>
                            <select name="disciplina_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm">
                                <option value="">Todas</option>
                                @foreach ($disciplinas as $d)
                                    <option value="{{ $d->id }}" @selected((int) $disciplinaId === (int) $d->id)>
                                        {{ $d->codigo_completo }} — {{ $d->nome }}
                                        @if ($d->periodo)
                                            ({{ $d->periodo->ano }}/{{ $d->periodo->semestre }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2 flex items-end">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 dark:bg-gray-200 dark:text-gray-900 dark:hover:bg-white">
                                Filtrar
                            </button>
                        </div>
                    </form>

                    <x-data-table caption="Inscrições para avaliação do professor">
                            <thead>
                                <tr class="text-left text-gray-600 dark:text-gray-400">
                                    <th class="px-4 py-3 font-semibold">Nome</th>
                                    <th class="px-4 py-3 font-semibold">Secretaria</th>
                                    <th class="px-4 py-3 font-semibold">Professor</th>
                                    <th class="px-4 py-3 font-semibold">Disciplina(s)</th>
                                    <th class="px-4 py-3 font-semibold">Período</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inscricoes as $i)
                                    <tr class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                        <td class="px-4 py-4 rounded-l-lg">
                                            <a href="{{ route('professor.inscricoes.show', $i) }}" class="font-medium text-blue-700 dark:text-blue-400 hover:underline">
                                                {{ $i->nome_completo }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-4">
                                            <x-inscricao-resumo-etapa-badge :resumo="$i->resumoAprovacaoSecretaria()" etapa="secretaria" />
                                        </td>
                                        <td class="px-4 py-4">
                                            <x-inscricao-resumo-etapa-badge :resumo="$i->resumoAprovacaoProfessor()" etapa="professor" />
                                        </td>
                                        <td class="px-4 py-4 text-gray-800 dark:text-gray-200">
                                            @php
                                                $codigos = [];
                                                if ($i->disciplinaObrigatoria) {
                                                    $codigos[] = $i->disciplinaObrigatoria->codigo_completo;
                                                }
                                                if ($i->disciplinaOpcional1) {
                                                    $codigos[] = $i->disciplinaOpcional1->codigo_completo;
                                                }
                                                if ($i->disciplinaOpcional2) {
                                                    $codigos[] = $i->disciplinaOpcional2->codigo_completo;
                                                }
                                            @endphp
                                            {{ count($codigos) ? implode(', ', $codigos) : '—' }}
                                        </td>
                                        <td class="px-4 py-4 rounded-r-lg">
                                            @if ($i->periodo)
                                                {{ $i->periodo->ano }}/{{ $i->periodo->semestre }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-600 dark:text-gray-400">
                                            Nenhuma inscrição disponível para avaliação do professor.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                    </x-data-table>

                    <div>
                        {{ $inscricoes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
