<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInscricaoRequest;
use App\Http\Requests\UpdateInscricaoRequest;
use App\Models\Inscricao;
use Uspdev\Replicado\Posgraduacao;

class InscricaoController extends Controller
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
        //
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInscricaoRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Inscricao $inscricao)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inscricao $inscricao)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInscricaoRequest $request, Inscricao $inscricao)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inscricao $inscricao)
    {
        //
    }
}
