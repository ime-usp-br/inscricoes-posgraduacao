<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inscrições
            </h2>
            <x-back-link :href="route('secretaria')" label="Voltar à Secretaria" />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('inscricao._flash-aprovacao')
            @if ($errors->any() && ! $errors->has('aprovacao'))
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-red-800 dark:text-red-200 text-sm space-y-1">
                    @foreach ($errors->all() as $erro)
                        <p>{{ $erro }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                    <form method="GET" action="{{ route('inscricoes.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Busca por nome</label>
                            <input type="search" name="q" value="{{ $search }}" placeholder="Nome completo..."
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div class="md:col-span-2">
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
                        <div class="md:col-span-2">
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
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Aprovação</label>
                            <select name="aprovacao" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm">
                                <option value="">Todas</option>
                                <optgroup label="Secretaria">
                                    <option value="secretaria_pendente" @selected($filtroAprovacao === 'secretaria_pendente')>Pendente (secretaria)</option>
                                    <option value="secretaria_aprovada" @selected($filtroAprovacao === 'secretaria_aprovada')>Aprovada pela secretaria</option>
                                    <option value="secretaria_reprovada" @selected($filtroAprovacao === 'secretaria_reprovada')>Reprovada pela secretaria</option>
                                </optgroup>
                                <optgroup label="Professor">
                                    <option value="professor_pendente" @selected($filtroAprovacao === 'professor_pendente')>Pendente (professor)</option>
                                    <option value="professor_aprovada" @selected($filtroAprovacao === 'professor_aprovada')>Aprovada pelo professor</option>
                                    <option value="professor_reprovada" @selected($filtroAprovacao === 'professor_reprovada')>Reprovada pelo professor</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pagamento</label>
                            <select name="pagamento" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm">
                                <option value="" @selected($filtroPagamento === '')>Todos</option>
                                <option value="nao_pago" @selected($filtroPagamento === 'nao_pago')>Não pago</option>
                                <option value="demais" @selected($filtroPagamento === 'demais')>Pagos e não aplicáveis</option>
                            </select>
                        </div>
                        <div class="md:col-span-2 flex items-end">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 dark:bg-gray-200 dark:text-gray-900 dark:hover:bg-white">
                                Filtrar
                            </button>
                        </div>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('inscricoes.aprovar-bloco-secretaria') }}"
                        class="space-y-4"
                        x-data="{
                            selecionadas: 0,
                            atualizar() {
                                this.selecionadas = $el.querySelectorAll('.inscricao-bulk-checkbox:checked').length;
                            },
                            marcarTodas(checked) {
                                $el.querySelectorAll('.inscricao-bulk-checkbox').forEach((cb) => { cb.checked = checked; });
                                this.atualizar();
                            }
                        }"
                        @submit="if (selecionadas === 0) { $event.preventDefault(); return; } if (! confirm('Aprovar todas as disciplinas pendentes das inscrições selecionadas?')) { $event.preventDefault(); }"
                    >
                        @csrf
                        <input type="hidden" name="q" value="{{ $search }}">
                        <input type="hidden" name="periodo_id" value="{{ $periodoId ?: '' }}">
                        <input type="hidden" name="disciplina_id" value="{{ $disciplinaId ?: '' }}">
                        <input type="hidden" name="aprovacao" value="{{ $filtroAprovacao }}">
                        <input type="hidden" name="pagamento" value="{{ $filtroPagamento }}">
                        <input type="hidden" name="page" value="{{ request('page') }}">

                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 px-4 py-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Selecione inscrições e aprove de uma vez todas as disciplinas ainda não aprovadas pela secretaria.
                            </p>
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-green-700/30 hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="selecionadas === 0"
                            >
                                Aprovar selecionadas (<span x-text="selecionadas">0</span>)
                            </button>
                        </div>

                    <x-data-table caption="Inscrições">
                            <thead>
                                <tr class="text-left text-gray-600 dark:text-gray-400">
                                    <th class="px-4 py-3 font-semibold w-10">
                                        <span class="sr-only">Selecionar</span>
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500"
                                            title="Selecionar todas elegíveis nesta página"
                                            @change="marcarTodas($event.target.checked)"
                                        >
                                    </th>
                                    <th class="px-4 py-3 font-semibold">Nome</th>
                                    <th class="px-4 py-3 font-semibold">Pagamento</th>
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
                                            @if ($i->podeAprovarEmBlocoSecretaria())
                                                <input
                                                    type="checkbox"
                                                    name="inscricoes[]"
                                                    value="{{ $i->id }}"
                                                    class="inscricao-bulk-checkbox rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500"
                                                    @change="atualizar()"
                                                >
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('inscricoes.show', $i) }}" class="font-medium text-blue-700 dark:text-blue-400 hover:underline">
                                                    {{ $i->nome_completo }}
                                                </a>
                                                <x-inscricao-duplicata-badge :inscricao="$i" />
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <x-inscricao-resumo-pagamento-badge :resumo="$i->resumoPagamento()" />
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
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-600 dark:text-gray-400">
                                            Nenhuma inscrição encontrada.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                    </x-data-table>
                    </form>

                    <div>
                        {{ $inscricoes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
