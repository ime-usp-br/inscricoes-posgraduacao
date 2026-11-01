<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Isenção de taxa
    |--------------------------------------------------------------------------
    |
    | A chave "habilitado" permite publicar a estrutura sem expor o fluxo
    | enquanto o backfill de pagamentos e os testes de aceite são executados.
    |
    */

    'habilitado' => (bool) env('ISENCAO_HABILITADO', false),

    'documentos' => [
        'max_arquivos' => (int) env('ISENCAO_MAX_ARQUIVOS', 5),
        'max_kb' => (int) env('ISENCAO_MAX_KB', 1024),
        'mimes' => ['pdf'],
        'disco' => env('ISENCAO_DISCO', 'local'),
        'diretorio' => 'isencao',
    ],

    'justificativa' => [
        'min' => 20,
        'max' => 2000,
    ],

    'prazo_pagamento_dias' => (int) env('ISENCAO_PRAZO_PAGAMENTO_DIAS', 5),

    'lembrete_dias_antes' => (int) env('ISENCAO_LEMBRETE_DIAS_ANTES', 2),

    'link_validade_dias' => (int) env('ISENCAO_LINK_VALIDADE_DIAS', 30),

    'sla_dias_uteis' => (int) env('ISENCAO_SLA_DIAS_UTEIS', 2),

];
