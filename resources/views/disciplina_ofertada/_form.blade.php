@csrf

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Período</label>
        <select name="periodo_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Selecione</option>
            @foreach ($periodos as $p)
                <option value="{{ $p->id }}" @selected(old('periodo_id', $disciplina->periodo_id) == $p->id)>
                    {{ $p->ano }}/{{ $p->semestre }}
                </option>
            @endforeach
        </select>
        @error('periodo_id')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Departamento</label>
            <select name="departamento" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Selecione</option>
                @foreach (['MAT','MAC','MAP','MAE'] as $dep)
                    <option value="{{ $dep }}" @selected(old('departamento', $disciplina->departamento) === $dep)>{{ $dep }}</option>
                @endforeach
            </select>
            @error('departamento')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Código (4 dígitos)</label>
            <input name="codigo" maxlength="4" inputmode="numeric" pattern="\d{4}" required
                   value="{{ old('codigo', $disciplina->codigo) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('codigo')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Nome da disciplina</label>
        <input name="nome" required
               value="{{ old('nome', $disciplina->nome) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        @error('nome')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Nome do professor</label>
            <input name="professor_nome" required
                   value="{{ old('professor_nome', $disciplina->professor_nome) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('professor_nome')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">E-mail do professor</label>
            <input name="professor_email" type="email" required
                   value="{{ old('professor_email', $disciplina->professor_email) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('professor_email')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="rounded-md bg-gray-50 border border-gray-200 p-3 text-sm text-gray-700">
        <span class="font-semibold">Código completo:</span>
        <span>
            {{ strtoupper(old('departamento', $disciplina->departamento ?? '')) }}{{ str_pad((string) old('codigo', $disciplina->codigo ?? ''), 4, '0', STR_PAD_LEFT) }}
        </span>
    </div>
</div>

