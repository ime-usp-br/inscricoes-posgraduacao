<?php

namespace Tests\Unit\Services;

use App\Services\LaravelLogParser;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LaravelLogParserTest extends TestCase
{
    public function test_parse_returns_empty_collection_when_log_file_does_not_exist(): void
    {
        $parser = new class extends LaravelLogParser
        {
            public function getLogPath(): string
            {
                return storage_path('logs/nonexistent-test.log');
            }
        };

        $this->assertTrue($parser->parse()->isEmpty());
    }

    public function test_parse_splits_laravel_log_into_entries(): void
    {
        $path = storage_path('logs/laravel-log-parser-test.log');

        File::put($path, <<<'LOG'
[2026-06-19 10:00:00] local.INFO: First message
[2026-06-19 10:00:01] local.ERROR: Second message
Context line
Stack trace line
[2026-06-19 10:00:02] local.WARNING: Third message
LOG);

        $parser = new class($path) extends LaravelLogParser
        {
            public function __construct(private string $path) {}

            public function getLogPath(): string
            {
                return $this->path;
            }
        };

        $entries = $parser->parse()->values();

        $this->assertCount(3, $entries);

        $newest = $entries->first();
        $this->assertSame('2026-06-19 10:00:02', $newest['datetime']);
        $this->assertSame('WARNING', $newest['level']);
        $this->assertSame('Third message', $newest['message']);

        $errorEntry = $entries->get(1);
        $this->assertSame('ERROR', $errorEntry['level']);
        $this->assertStringContainsString("Context line\nStack trace line", $errorEntry['body']);

        File::delete($path);
    }
}
