<?php

namespace App\Services;

use App\Contracts\WspixClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WspixHttpClient implements WspixClient
{
    public function gerar(array $payload): array
    {
        return $this->request('post', '/pix/gerar', $payload);
    }

    public function consultar(string $idfpix, bool $verificar = false): array
    {
        $query = $verificar ? ['verificar' => '1'] : [];

        return $this->request('get', '/pix/'.urlencode($idfpix), [], $query);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = [], array $query = []): array
    {
        $username = config('wspix.username');
        $password = config('wspix.password');

        if (! is_string($username) || $username === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException('Credenciais WSPIX não configuradas (WSPIX_USERNAME / WSPIX_PASSWORD).');
        }

        $url = rtrim((string) config('wspix.base_url'), '/').$path;
        $logContext = [
            'method' => $method,
            'path' => $path,
            'url' => $url,
            'query' => $query,
            'body' => $this->sanitizeBodyForLogs($body),
        ];

        try {
            $pending = Http::withHeaders([
                'X-Username' => $username,
                'X-Password' => $password,
                'Accept' => 'application/json',
            ])->timeout(30);

            $response = match ($method) {
                'post' => $pending->post($url, $body),
                'get' => $pending->get($url, $query),
                default => throw new RuntimeException('Método HTTP não suportado.'),
            };
        } catch (RequestException $exception) {
            Log::error('Falha de comunicação com WSPIX.', array_merge($logContext, [
                'error' => $exception->getMessage(),
            ]));

            throw new RuntimeException(
                'Falha na comunicação com o WSPIX: '.$exception->getMessage(),
                0,
                $exception
            );
        }

        if (! $response->successful()) {
            Log::warning('WSPIX retornou resposta de erro.', array_merge($logContext, [
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]));

            throw new RuntimeException(
                'WSPIX retornou erro HTTP '.$response->status().': '.$response->body()
            );
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Resposta inválida do WSPIX.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function sanitizeBodyForLogs(array $body): array
    {
        $sanitized = $body;

        if (isset($sanitized['docPesOrg']) && is_string($sanitized['docPesOrg'])) {
            $sanitized['docPesOrg'] = substr($sanitized['docPesOrg'], 0, 3).'***';
        }

        return $sanitized;
    }
}
