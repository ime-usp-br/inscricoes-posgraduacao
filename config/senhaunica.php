<?php

/**
 * Configuração da Senha Única (uspdev/senhaunica-socialite).
 *
 * Este sistema usa papéis Spatie (Admin, Secretario, Professor) no guard web.
 * Com permission=false, o login não consulta o Replicado para montar permissões
 * de vínculo (ALUNOGR/ALUNOPOS). O Replicado continua disponível na importação
 * de disciplinas ao criar período.
 */
return array_merge(
    require __DIR__.'/../vendor/uspdev/senhaunica-socialite/config/config.php',
    [
        'permission' => (bool) env('SENHAUNICA_PERMISSION', false),
    ]
);
