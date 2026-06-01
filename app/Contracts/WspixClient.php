<?php

namespace App\Contracts;

interface WspixClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function gerar(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function consultar(string $idfpix, bool $verificar = false): array;
}
