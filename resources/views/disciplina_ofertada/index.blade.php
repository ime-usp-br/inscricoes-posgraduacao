<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Disciplinas ofertadas
            </h2>

            <div class="flex flex-wrap items-center gap-3">
                <x-back-link :href="route('secretaria')" label="Voltar à Secretaria" />
            <a href="{{ route('disciplina-ofertada.create') }}"
               class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                Nova disciplina
            </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="rounded-md bg-green-50 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 space-y-4">
                    <form method="GET" action="{{ route('disciplina-ofertada.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700">Busca</label>
                            <input name="q" value="{{ $search }}"
                                   placeholder="nome, professor, email ou MAC0123..."
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Departamento</label>
                            <select name="departamento"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos</option>
                                @foreach (['MAT','MAC','MAP','MAE'] as $dep)
                                    <option value="{{ $dep }}" @selected($departamento === $dep)>{{ $dep }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Período</label>
                            <select name="periodo_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos</option>
                                @foreach ($periodos as $p)
                                    <option value="{{ $p->id }}" @selected((int) $periodoId === (int) $p->id)>
                                        {{ $p->ano }}/{{ $p->semestre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Semestre</label>
                            <select name="semestre"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos</option>
                                <option value="1" @selected((int) $semestre === 1)>1</option>
                                <option value="2" @selected((int) $semestre === 2)>2</option>
                            </select>
                        </div>

                        <div class="md:col-span-1 flex items-end gap-2">
                            <button class="inline-flex w-full justify-center items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                                Filtrar
                            </button>
                        </div>

                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="hidden" name="dir" value="{{ $dir }}">
                    </form>

                    @php
                        $qsBase = request()->except(['page']);
                        $toggleDir = fn ($col) => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
                        $sortLink = fn ($col) => route('disciplina-ofertada.index', array_merge($qsBase, ['sort' => $col, 'dir' => $toggleDir($col)]));
                    @endphp

                    <x-data-table caption="Disciplinas ofertadas">
                            <thead>
                                <tr class="text-left text-gray-600">
                                    <th class="px-4 py-3 font-semibold">
                                        <a class="hover:underline" href="{{ $sortLink('codigo_completo') }}">Código</a>
                                    </th>
                                    <th class="px-4 py-3 font-semibold">
                                        <a class="hover:underline" href="{{ $sortLink('nome') }}">Nome</a>
                                    </th>
                                    <th class="px-4 py-3 font-semibold">
                                        <a class="hover:underline" href="{{ $sortLink('professor_nome') }}">Professor</a>
                                    </th>
                                    <th class="px-4 py-3 font-semibold">
                                        <a class="hover:underline" href="{{ $sortLink('professor_email') }}">E-mail</a>
                                    </th>
                                    <th class="px-4 py-3 font-semibold">
                                        <a class="hover:underline" href="{{ $sortLink('periodo') }}">Período</a>
                                    </th>
                                    <th class="px-4 py-3 font-semibold text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($disciplinas as $d)
                                    <tr class="bg-white shadow-sm ring-1 ring-gray-200 {{ (int) session('highlight_id') === (int) $d->id ? '!bg-yellow-50 !ring-yellow-200' : '' }}">
                                        <td class="px-4 py-4 font-semibold rounded-l-lg">
                                            <a class="text-blue-700 hover:underline" href="{{ route('disciplina-ofertada.show', $d) }}">
                                                {{ $d->codigo_completo }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-4">{{ $d->nome }}</td>
                                        <td class="px-4 py-4">{{ $d->professor_nome }}</td>
                                        <td class="px-4 py-4">{{ $d->professor_email }}</td>
                                        <td class="px-4 py-4">
                                            {{ $d->periodo?->ano }}/{{ $d->periodo?->semestre }}
                                        </td>
                                        <td class="px-4 py-4 text-right rounded-r-lg">
                                            <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                                <x-table-action-edit :href="route('disciplina-ofertada.edit', $d)" />
                                                <x-table-action-delete
                                                    :action="route('disciplina-ofertada.destroy', $d)"
                                                    confirm="Tem certeza que deseja excluir esta disciplina?" />
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-700">
                                            Nenhuma disciplina encontrada.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                    </x-data-table>

                    <div>
                        {{ $disciplinas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

