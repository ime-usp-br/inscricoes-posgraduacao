@props([
    'inscricao',
    'editavel' => true,
])

@php
    $disciplinasProfessor = $inscricao->disciplinasParaAprovacaoProfessor();
@endphp

@if ($disciplinasProfessor !== [])
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg ring-1 ring-indigo-200 dark:ring-indigo-800">
        <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
            <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">
                Aprovação pelo Professor (2ª etapa)
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @if ($editavel)
                    Aprove ou reprove cada disciplina disponível para esta etapa. Com ao menos uma aprovação, a inscrição
                    aparece como <strong>Aprovada pelo Professor</strong>; se todas forem reprovadas, como
                    <strong>Reprovada pelo Professor</strong>.
                @else
                    Situação da avaliação final do professor para as disciplinas aprovadas pela secretaria.
                @endif
            </p>

            <div class="flex flex-col gap-4">
                @foreach ($disciplinasProfessor as $item)
                    @php
                        $disciplina = $item['disciplina'];
                        $slot = $item['slot'];
                        $codigo = $disciplina->codigo_completo;
                        $justificativa = $item['justificativa'] ?? null;
                        $aprovacao = $inscricao->aprovacaoProfessorParaSlot($slot);
                        $jaAprovada = $aprovacao === \App\Enums\AprovacaoProfessorDisciplina::Aprovado;
                        $jaReprovada = $aprovacao === \App\Enums\AprovacaoProfessorDisciplina::Reprovado;
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 px-4 py-3">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $codigo }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $disciplina->nome }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $disciplina->professor_nome ?: 'Professor não informado' }}</p>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">
                                <span class="font-medium">Justificativa:</span> {{ $justificativa ?: '—' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($jaAprovada)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800">
                                    Aprovada pelo Professor
                                </span>
                            @elseif ($jaReprovada)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800">
                                    Reprovada pelo Professor
                                </span>
                            @elseif (! $editavel)
                                <span class="text-sm text-gray-500 dark:text-gray-400">Pendente</span>
                            @endif

                            @if ($editavel && ! $jaAprovada)
                                <form method="POST" action="{{ route('professor.inscricoes.aprovar', $inscricao) }}">
                                    @csrf
                                    <input type="hidden" name="disciplina" value="{{ $slot }}">
                                    <button type="submit"
                                            class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-green-700/30 hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                                        Aprovar {{ $codigo }}
                                    </button>
                                </form>
                            @endif

                            @if ($editavel && ! $jaReprovada)
                                <form method="POST" action="{{ route('professor.inscricoes.reprovar', $inscricao) }}">
                                    @csrf
                                    <input type="hidden" name="disciplina" value="{{ $slot }}">
                                    <button type="submit"
                                            class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-red-700/30 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">
                                        Reprovar {{ $codigo }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
