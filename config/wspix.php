<?php

return [

  /*
  |--------------------------------------------------------------------------
  | WSPIX (USP Digital)
  |--------------------------------------------------------------------------
  |
  | Credenciais iguais às do wsboleto (headers X-Username e X-Password).
  | Homologação: codigoFonteRecurso=423, codigoUnidadeDespesa=1,
  | estruturaHierarquica="\DISTRIBUIDOR"
  |
  */

    'base_url' => env('WSPIX_BASE_URL', 'https://api-dev.portalservicos.usp.br/wspix/api'),

    'username' => env('WSPIX_USERNAME'),

    'password' => env('WSPIX_PASSWORD'),

    'codigo_fonte_recurso' => (int) env('WSPIX_CODIGO_FONTE_RECURSO', 423),

    'codigo_unidade_despesa' => (int) env('WSPIX_CODIGO_UNIDADE_DESPESA', 1),

    'estrutura_hierarquica' => env('WSPIX_ESTRUTURA_HIERARQUICA', '\\DISTRIBUIDOR'),

    'valor_por_disciplina' => (float) env('WSPIX_VALOR_POR_DISCIPLINA', 107),

    'expiracao_segundos' => (int) env('WSPIX_EXPIRACAO_SEGUNDOS', 86400),

    'webhook_token' => env('WSPIX_WEBHOOK_TOKEN'),

];
