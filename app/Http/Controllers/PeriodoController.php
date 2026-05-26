<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeriodoRequest;
use App\Http\Requests\UpdatePeriodoRequest;
use App\Models\Periodo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PeriodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $periodos = Periodo::query()
            ->orderByDesc('ano')
            ->orderByDesc('semestre')
            ->get();

        return view('periodo.index', compact('periodos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        // if(!Auth::check()){
        //     return redirect()->route('login');
        // }elseif(!Auth::user()->hasRole('admin')){
        //     abort(403);
        // }

        $periodo = new Periodo();
        return view('periodo.create', compact('periodo'));
    }

    public function confirmar(StorePeriodoRequest $request): View
    {
        // if(!Auth::check()){
        //     return redirect()->route('login');
        // }elseif(!Auth::user()->hasRole('admin')){
        //     abort(403);
        // }

        /** @var array{ano:int,semestre:int,data_inicio_inscricao:string,data_fim_inscricao:string} $validated */
        $validated = $request->validated();

        $disciplinas = DB::select("
            SELECT *
            FROM OFERECIMENTO
            WHERE dtainiofe > '{$validated['data_inicio_inscricao']}' AND
            (sgldis LIKE 'MAC%' OR sgldis LIKE 'MAT%' OR sgldis LIKE 'MAE%' OR sgldis LIKE 'MAP%')
            ORDER BY sgldis;
        ");

        dd($disciplinas);

        return view('periodo.confirmar', compact('validated'));
    }

    public function salvar(StorePeriodoRequest $request): RedirectResponse
    {
        // if(!Auth::check()){
        //     return redirect()->route('login');
        // }elseif(!Auth::user()->hasRole('admin')){
        //     abort(403);
        // }

        /** @var array{ano:int,semestre:int,data_inicio_inscricao:string,data_fim_inscricao:string} $validated */
        $validated = $request->validated();

        $periodo = Periodo::create($validated);

        return redirect()->route('periodo.show', $periodo)
            ->with('success', 'Período criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Periodo $periodo): View
    {
        return view('periodo.show', compact('periodo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Periodo $periodo): View
    {
        return view('periodo.edit', compact('periodo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePeriodoRequest $request, Periodo $periodo): RedirectResponse
    {
        $validated = $request->validated();
        $periodo->update($validated);

        return redirect()->route('periodo.index')
            ->with('success', 'Período atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Periodo $periodo): RedirectResponse
    {
        abort_unless(auth()->user()?->canDeleteSecretariaResources(), 403, 'Somente administradores podem excluir períodos.');

        $periodo->delete();

        return redirect()->route('periodo.index')
            ->with('success', 'Período excluído com sucesso.');
    }
}
