<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDisciplinaOfertadaRequest;
use App\Http\Requests\UpdateDisciplinaOfertadaRequest;
use App\Models\DisciplinaOfertada;
use App\Models\Periodo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DisciplinaOfertadaController extends Controller
{
    public function index(Request $request): View
    {
        $departamento = $request->string('departamento')->toString();
        $periodoId = $request->integer('periodo_id');
        $semestre = $request->integer('semestre');
        $search = trim($request->string('q')->toString());

        $allowedSort = [
            'codigo_completo',
            'nome',
            'professor_nome',
            'professor_email',
            'departamento',
            'codigo',
            'periodo',
            'created_at',
        ];
        $sort = $request->string('sort')->toString() ?: 'codigo_completo';
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'codigo_completo';
        }
        $dir = strtolower($request->string('dir')->toString() ?: 'asc');
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

        $query = DisciplinaOfertada::query()->with('periodo');

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('professor_nome', 'like', "%{$search}%")
                    ->orWhere('professor_email', 'like', "%{$search}%")
                    ->orWhereRaw("concat(departamento, codigo) like ?", ["%{$search}%"]);
            });
        }

        if (in_array($departamento, ['MAT', 'MAC', 'MAP', 'MAE', 'MPM'], true)) {
            $query->where('departamento', $departamento);
        }

        if ($periodoId > 0) {
            $query->where('periodo_id', $periodoId);
        }

        if (in_array($semestre, [1, 2], true)) {
            $query->whereHas('periodo', fn (Builder $q) => $q->where('semestre', $semestre));
        }

        if ($sort === 'codigo_completo') {
            $query->orderBy('departamento', $dir)->orderBy('codigo', $dir);
        } elseif ($sort === 'periodo') {
            $query->whereHas('periodo')
                ->join('periodos', 'periodos.id', '=', 'disciplinas_ofertadas.periodo_id')
                ->orderBy('periodos.ano', $dir)
                ->orderBy('periodos.semestre', $dir)
                ->select('disciplinas_ofertadas.*');
        } else {
            $query->orderBy($sort, $dir);
        }

        $disciplinas = $query->paginate(15)->withQueryString();

        $periodos = Periodo::query()
            ->orderByDesc('ano')
            ->orderByDesc('semestre')
            ->get();

        return view('disciplina_ofertada.index', compact(
            'disciplinas',
            'periodos',
            'departamento',
            'periodoId',
            'semestre',
            'search',
            'sort',
            'dir',
        ));
    }

    public function create(Request $request): View
    {
        $disciplina = new DisciplinaOfertada([
            'periodo_id' => $request->integer('periodo_id') ?: null,
            'departamento' => $request->string('departamento')->toString() ?: null,
        ]);

        $periodos = Periodo::query()->orderByDesc('ano')->orderByDesc('semestre')->get();

        return view('disciplina_ofertada.create', compact('disciplina', 'periodos'));
    }

    public function store(StoreDisciplinaOfertadaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $disciplina = DisciplinaOfertada::create($validated);

        return redirect()->route('disciplina-ofertada.index')
            ->with('success', 'Disciplina cadastrada com sucesso.')
            ->with('highlight_id', $disciplina->id);
    }

    public function show(DisciplinaOfertada $disciplina_ofertada): View
    {
        $disciplina_ofertada->load('periodo');
        return view('disciplina_ofertada.show', ['disciplina' => $disciplina_ofertada]);
    }

    public function edit(DisciplinaOfertada $disciplina_ofertada): View
    {
        $periodos = Periodo::query()->orderByDesc('ano')->orderByDesc('semestre')->get();
        return view('disciplina_ofertada.edit', ['disciplina' => $disciplina_ofertada, 'periodos' => $periodos]);
    }

    public function update(UpdateDisciplinaOfertadaRequest $request, DisciplinaOfertada $disciplina_ofertada): RedirectResponse
    {
        $validated = $request->validated();
        $disciplina_ofertada->update($validated);

        return redirect()->route('disciplina-ofertada.index')
            ->with('success', 'Disciplina atualizada com sucesso.');
    }

    public function destroy(DisciplinaOfertada $disciplina_ofertada): RedirectResponse
    {
        abort_unless(auth()->user()?->canDeleteSecretariaResources(), 403, 'Somente administradores podem excluir disciplinas.');

        $disciplina_ofertada->delete();

        return redirect()->route('disciplina-ofertada.index')
            ->with('success', 'Disciplina excluída com sucesso.');
    }
}

