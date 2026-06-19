<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LaravelLogParser
{
    private const ENTRY_PATTERN = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] ([^.]+)\.([^:]+): (.*)$/';

    public function getLogPath(): string
    {
        return storage_path('logs/laravel.log');
    }

    /**
     * @return Collection<int, array{
     *     key: int,
     *     datetime: string,
     *     environment: string,
     *     level: string,
     *     message: string,
     *     preview: string,
     *     body: string,
     * }>
     */
    public function parse(): Collection
    {
        $path = $this->getLogPath();

        if (! File::exists($path) || File::size($path) === 0) {
            return collect();
        }

        $entries = [];
        $current = null;
        $index = 0;

        $handle = fopen($path, 'r');

        if ($handle === false) {
            return collect();
        }

        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");

            if (preg_match(self::ENTRY_PATTERN, $line, $matches)) {
                if ($current !== null) {
                    $entries[] = $this->finalizeEntry($current, $index++);
                }

                $current = [
                    'datetime' => $matches[1],
                    'environment' => $matches[2],
                    'level' => strtoupper($matches[3]),
                    'message' => $matches[4],
                    'lines' => [$line],
                ];

                continue;
            }

            if ($current !== null) {
                $current['lines'][] = $line;
            }
        }

        fclose($handle);

        if ($current !== null) {
            $entries[] = $this->finalizeEntry($current, $index);
        }

        return collect($entries)->reverse()->values();
    }

    /**
     * @param  array{
     *     datetime: string,
     *     environment: string,
     *     level: string,
     *     message: string,
     *     lines: array<int, string>,
     * }  $current
     * @return array{
     *     key: int,
     *     datetime: string,
     *     environment: string,
     *     level: string,
     *     message: string,
     *     preview: string,
     *     body: string,
     * }
     */
    private function finalizeEntry(array $current, int $index): array
    {
        $body = implode("\n", $current['lines']);
        $preview = Str::limit($current['message'], 120);

        return [
            'key' => $index,
            'datetime' => $current['datetime'],
            'environment' => $current['environment'],
            'level' => $current['level'],
            'message' => $current['message'],
            'preview' => $preview,
            'body' => $body,
        ];
    }
}
