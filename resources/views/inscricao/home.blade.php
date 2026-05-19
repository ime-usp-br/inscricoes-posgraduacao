<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inscrição — Período {{ $periodo->ano }}/{{ $periodo->semestre }}
            </h2>
            @if ($inscricao)
                <form method="POST" action="{{ route('inscricao.reiniciar') }}" onsubmit="return confirm('Descartar o progresso atual e recomeçar?');">
                    @csrf
                    <button type="submit" class="text-sm text-red-700 dark:text-red-400 hover:underline">
                        Reiniciar inscrição
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-red-800 dark:text-red-200 text-sm space-y-1">
                    @foreach ($errors->all() as $erro)
                        <p>{{ $erro }}</p>
                    @endforeach
                </div>
            @endif

            @if (session('success'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-green-800 dark:text-green-200 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex gap-2 text-sm">
                <span class="rounded-full px-3 py-1 {{ $passo === 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">1. Dados pessoais</span>
                <span class="rounded-full px-3 py-1 {{ $passo === 2 ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">2. Complemento</span>
                <span class="rounded-full px-3 py-1 {{ $passo === 3 ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">3. Disciplinas</span>
            </div>

            {{-- Etapa 1 --}}
            @if ($passo === 1)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                        <h3 class="text-lg font-semibold">Etapa 1 — Dados pessoais</h3>
                        <form method="POST" action="{{ route('inscricao.etapa1') }}" class="space-y-4" id="form-etapa1">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome completo</label>
                                <input type="text" name="nome_completo" required value="{{ old('nome_completo', $inscricao->nome_completo ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                @error('nome_completo')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</label>
                                <input type="email" name="email" required value="{{ old('email', $inscricao->email ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <fieldset>
                                <legend class="block text-sm font-medium text-gray-700 dark:text-gray-300">Aluno USP?</legend>
                                <div class="mt-2 flex gap-4">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="aluno_usp" value="sim" class="rounded-full border-gray-300" {{ old('aluno_usp', ($inscricao?->aluno_usp === true) ? 'sim' : (($inscricao?->aluno_usp === false) ? 'nao' : '')) === 'sim' ? 'checked' : '' }} />
                                        <span>Sim</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="aluno_usp" value="nao" class="rounded-full border-gray-300" {{ old('aluno_usp', ($inscricao?->aluno_usp === false) ? 'nao' : '') === 'nao' ? 'checked' : '' }} />
                                        <span>Não</span>
                                    </label>
                                </div>
                                @error('aluno_usp')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </fieldset>
                            <div id="wrap-numero-usp" class="{{ old('aluno_usp', ($inscricao?->aluno_usp) ? 'sim' : 'nao') === 'sim' ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número USP</label>
                                <input type="text" name="numero_usp" value="{{ old('numero_usp', $inscricao->numero_usp ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                @error('numero_usp')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            @error('fluxo')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            <div>
                                <x-primary-button>Continuar</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                    (function () {
                        const form = document.getElementById('form-etapa1');
                        if (!form) return;
                        const wrap = document.getElementById('wrap-numero-usp');
                        const nusp = form.querySelector('input[name="numero_usp"]');
                        form.querySelectorAll('input[name="aluno_usp"]').forEach(function (r) {
                            r.addEventListener('change', function () {
                                if (r.value === 'sim' && r.checked) {
                                    wrap.classList.remove('hidden');
                                    nusp.setAttribute('required', 'required');
                                } else if (r.value === 'nao' && r.checked) {
                                    wrap.classList.add('hidden');
                                    nusp.removeAttribute('required');
                                    nusp.value = '';
                                }
                            });
                        });
                    })();
                </script>
            @endif

            {{-- Etapa 2 — USP --}}
            @if ($passo === 2 && $inscricao->aluno_usp)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                        <h3 class="text-lg font-semibold">Etapa 2 — Aluno USP</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $inscricao->nome_completo }} — {{ $inscricao->email }}</p>
                        <form method="POST" action="{{ route('inscricao.etapa2') }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unidade</label>
                                <input type="text" name="unidade" required value="{{ old('unidade') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                @error('unidade')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comprovante de matrícula (PDF)</label>
                                <input type="file" name="pdf_comprovante_matricula" accept="application/pdf" required class="mt-1 block w-full text-sm" />
                                @error('pdf_comprovante_matricula')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Histórico escolar (PDF)</label>
                                <input type="file" name="pdf_historico_escolar" accept="application/pdf" required class="mt-1 block w-full text-sm" />
                                @error('pdf_historico_escolar')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            @error('sessao')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            <x-primary-button>Continuar para disciplinas</x-primary-button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Etapa 2 — não USP --}}
            @if ($passo === 2 && ! $inscricao->aluno_usp)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                        <h3 class="text-lg font-semibold">Etapa 2 — Dados complementares</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $inscricao->nome_completo }} — {{ $inscricao->email }}</p>
                        <form method="POST" action="{{ route('inscricao.etapa2') }}" enctype="multipart/form-data" class="space-y-4" id="form-etapa2-externo">
                            @csrf

                            <fieldset class="space-y-2">
                                <legend class="text-sm font-medium">Está matriculado em algum programa de Pós-Graduação externo à USP?</legend>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center gap-2"><input type="radio" name="pos_graduacao_externo" value="sim" {{ old('pos_graduacao_externo') === 'sim' ? 'checked' : '' }} required /> Sim</label>
                                    <label class="inline-flex items-center gap-2"><input type="radio" name="pos_graduacao_externo" value="nao" {{ old('pos_graduacao_externo') === 'nao' ? 'checked' : '' }} /> Não</label>
                                </div>
                                @error('pos_graduacao_externo')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            </fieldset>
                            <div>
                                <label class="block text-sm font-medium">Se respondeu sim, especifique o nome do programa</label>
                                <input type="text" name="nome_programa_externo" value="{{ old('nome_programa_externo') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                @error('nome_programa_externo')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>

                            <fieldset class="space-y-2">
                                <legend class="text-sm font-medium">Já fez algum curso na USP anteriormente?</legend>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center gap-2"><input type="radio" name="curso_usp_anterior" value="sim" {{ old('curso_usp_anterior') === 'sim' ? 'checked' : '' }} required /> Sim</label>
                                    <label class="inline-flex items-center gap-2"><input type="radio" name="curso_usp_anterior" value="nao" {{ old('curso_usp_anterior') === 'nao' ? 'checked' : '' }} /> Não</label>
                                </div>
                                @error('curso_usp_anterior')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            </fieldset>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">Data de nascimento</label>
                                    <input type="date" name="data_nascimento" required value="{{ old('data_nascimento') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                    @error('data_nascimento')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Gênero</label>
                                    <select name="genero" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm">
                                        <option value="">Selecione</option>
                                        @foreach (['Feminino','Masculino','Não-binário','Prefiro não informar','Outro'] as $g)
                                            <option value="{{ $g }}" @selected(old('genero') === $g)>{{ $g }}</option>
                                        @endforeach
                                    </select>
                                    @error('genero')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Nome da mãe (completo, sem abreviações)</label>
                                <input type="text" name="nome_mae" required value="{{ old('nome_mae') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                @error('nome_mae')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">CPF</label>
                                    <input type="text" name="cpf" required value="{{ old('cpf') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                    @error('cpf')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">RG / RNE / RNM</label>
                                    <input type="text" name="rg_rne_rnm" required value="{{ old('rg_rne_rnm') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                    @error('rg_rne_rnm')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Visto Estudante ou Mercosul (para estrangeiros — texto)</label>
                                <input type="text" name="visto_estudante_mercosul" value="{{ old('visto_estudante_mercosul') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                @error('visto_estudante_mercosul')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">Órgão expedidor</label>
                                    <input type="text" name="orgao_expedidor" required value="{{ old('orgao_expedidor') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                    @error('orgao_expedidor')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Estado de expedição</label>
                                    <input type="text" name="estado_expedicao" required value="{{ old('estado_expedicao') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                    @error('estado_expedicao')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Data de expedição</label>
                                <input type="date" name="data_expedicao" required value="{{ old('data_expedicao') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                @error('data_expedicao')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">País de nascimento</label>
                                    <input type="text" name="pais_nascimento" required value="{{ old('pais_nascimento') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                    @error('pais_nascimento')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Estado de nascimento</label>
                                    <input type="text" name="estado_nascimento" required value="{{ old('estado_nascimento') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                    @error('estado_nascimento')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Município / Província</label>
                                <input type="text" name="municipio_provincia" required value="{{ old('municipio_provincia') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                @error('municipio_provincia')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Nacionalidade</label>
                                <input type="text" name="nacionalidade" required value="{{ old('nacionalidade') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                @error('nacionalidade')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Endereço completo</label>
                                <textarea name="endereco_completo" required rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm">{{ old('endereco_completo') }}</textarea>
                                @error('endereco_completo')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium">CEP</label>
                                    <input type="text" name="cep" required value="{{ old('cep') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                    @error('cep')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Telefone / Celular</label>
                                    <input type="text" name="telefone" required value="{{ old('telefone') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm" />
                                    @error('telefone')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <fieldset class="space-y-2">
                                <legend class="text-sm font-medium">Estrangeiro?</legend>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center gap-2"><input type="radio" name="estrangeiro" value="sim" class="js-estrangeiro" {{ old('estrangeiro') === 'sim' ? 'checked' : '' }} required /> Sim</label>
                                    <label class="inline-flex items-center gap-2"><input type="radio" name="estrangeiro" value="nao" class="js-estrangeiro" {{ old('estrangeiro', 'nao') === 'nao' ? 'checked' : '' }} /> Não</label>
                                </div>
                                @error('estrangeiro')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            </fieldset>

                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Documentos (PDF)</p>
                            <div class="grid grid-cols-1 gap-3">
                                <div><label class="text-sm">Diploma de graduação</label><input type="file" name="pdf_diploma_graduacao" accept="application/pdf" required class="mt-1 block w-full text-sm" />@error('pdf_diploma_graduacao')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                                <div><label class="text-sm">Histórico da graduação</label><input type="file" name="pdf_historico_graduacao" accept="application/pdf" required class="mt-1 block w-full text-sm" />@error('pdf_historico_graduacao')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                                <div><label class="text-sm">RG / RNM / RNE</label><input type="file" name="pdf_rg_rne_rnm" accept="application/pdf" required class="mt-1 block w-full text-sm" />@error('pdf_rg_rne_rnm')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                                <div><label class="text-sm">CPF</label><input type="file" name="pdf_cpf" accept="application/pdf" required class="mt-1 block w-full text-sm" />@error('pdf_cpf')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                                <div id="wrap-pdf-estrangeiro" class="space-y-3 {{ old('estrangeiro') === 'sim' ? '' : 'hidden' }}">
                                    <div><label class="text-sm">Passaporte (apenas estrangeiros)</label><input type="file" name="pdf_passaporte" accept="application/pdf" class="mt-1 block w-full text-sm js-pdf-estrangeiro" />@error('pdf_passaporte')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                                    <div><label class="text-sm">Visto Estudante ou Mercosul (apenas estrangeiros)</label><input type="file" name="pdf_visto_estudante_mercosul" accept="application/pdf" class="mt-1 block w-full text-sm js-pdf-estrangeiro" />@error('pdf_visto_estudante_mercosul')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                                </div>
                            </div>

                            @error('sessao')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            <x-primary-button>Continuar para disciplinas</x-primary-button>
                        </form>
                        <script>
                            (function () {
                                const form = document.getElementById('form-etapa2-externo');
                                if (!form) return;
                                const wrap = document.getElementById('wrap-pdf-estrangeiro');
                                const inputs = wrap ? wrap.querySelectorAll('.js-pdf-estrangeiro') : [];
                                function sync() {
                                    const sim = form.querySelector('input[name="estrangeiro"][value="sim"]');
                                    const isSim = sim && sim.checked;
                                    if (!wrap) return;
                                    if (isSim) {
                                        wrap.classList.remove('hidden');
                                        inputs.forEach(function (i) { i.setAttribute('required', 'required'); });
                                    } else {
                                        wrap.classList.add('hidden');
                                        inputs.forEach(function (i) { i.removeAttribute('required'); i.value = ''; });
                                    }
                                }
                                form.querySelectorAll('.js-estrangeiro').forEach(function (r) { r.addEventListener('change', sync); });
                                sync();
                            })();
                        </script>
                    </div>
                </div>
            @endif

            {{-- Etapa 3 --}}
            @if ($passo === 3)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
                        <h3 class="text-lg font-semibold">Etapa 3 — Disciplinas</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Uma disciplina é obrigatória; até duas opcionais.</p>
                        <form method="POST" action="{{ route('inscricao.etapa3') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium">Disciplina obrigatória</label>
                                <select name="disciplina_obrigatoria_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm">
                                    <option value="">Selecione</option>
                                    @foreach ($disciplinas as $d)
                                        <option value="{{ $d->id }}" @selected(old('disciplina_obrigatoria_id') == $d->id)>
                                            {{ $d->codigo_completo }} — {{ $d->nome }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('disciplina_obrigatoria_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Disciplina opcional 1</label>
                                <select name="disciplina_opcional_1_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm">
                                    <option value="">— Nenhuma —</option>
                                    @foreach ($disciplinas as $d)
                                        <option value="{{ $d->id }}" @selected(old('disciplina_opcional_1_id') == $d->id)>
                                            {{ $d->codigo_completo }} — {{ $d->nome }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('disciplina_opcional_1_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Disciplina opcional 2</label>
                                <select name="disciplina_opcional_2_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm">
                                    <option value="">— Nenhuma —</option>
                                    @foreach ($disciplinas as $d)
                                        <option value="{{ $d->id }}" @selected(old('disciplina_opcional_2_id') == $d->id)>
                                            {{ $d->codigo_completo }} — {{ $d->nome }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('disciplina_opcional_2_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            @error('disciplinas')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            @error('sessao')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            <x-primary-button>Enviar inscrição</x-primary-button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
