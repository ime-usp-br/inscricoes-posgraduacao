<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeriodoRequest;
use App\Http\Requests\UpdatePeriodoRequest;
use App\Models\Periodo;
use Uspdev\Replicado\DB;

class PeriodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // if(!Auth::check()){
        //     return redirect()->route('login');
        // }elseif(!Auth::user()->hasRole('admin')){
        //     abort(403);
        // }

        $periodo = new Periodo();
        return view('periodo.create', compact('periodo'));
    }

    /**
     * Busca no Replicado as disciplinas de pós-graduação iniciando após a data de início das inscrições informada.
     */
    public function store(StorePeriodoRequest $request)
    {
        // if(!Auth::check()){
        //     return redirect()->route('login');
        // }elseif(!Auth::user()->hasRole('admin')){
        //     abort(403);
        // }

        $validated = $request->validated();

        $data_inicio = $validated['data_inicio_inscricao'];

        $sql = "
            SELECT *
            FROM OFERECIMENTO
            WHERE dtainiofe > '$data_inicio' AND
            (sgldis LIKE 'MAC%' OR sgldis LIKE 'MAT%' OR sgldis LIKE 'MAE%' OR sgldis LIKE 'MAP%')
            ORDER BY sgldis;
        ";

        $disciplinas = DB::fetchAll($sql);

        // foreach ($disciplinas as $disciplina){
        //     $sql = "
        //         SELECT *
        //         FROM R35DOCCOLTUR
        //         WHERE sgldis = '{$disciplina['sgldis']}' AND
        //         numofe = {$disciplina['numofe']};
        //     ";

        //     $result = DB::fetchAll($sql);
        //     dd($result);
        // }
        
        // dd($disciplinas);

        return view('periodo.confirmar', compact('validated', 'disciplinas'));
    }

    public function salvar(StorePeriodoRequest $request)
    {
        // if(!Auth::check()){
        //     return redirect()->route('login');
        // }elseif(!Auth::user()->hasRole('admin')){
        //     abort(403);
        // }

        $validated = $request->validated();

        $periodo = Periodo::create($validated);

        return redirect()->route('periodo.show', $periodo->id)
            ->with('success', 'Período criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Periodo $periodo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Periodo $periodo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePeriodoRequest $request, Periodo $periodo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Periodo $periodo)
    {
        //
    }
}
