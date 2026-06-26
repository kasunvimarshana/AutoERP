<?php

declare(strict_types=1);

namespace Modules\Auth\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

final class AuthIncidentLookupCommand extends Command
{
    protected $signature = 'auth:incident {correlationId : The support reference shown to the user}';

    protected $description = 'Find an Auth incident in local Laravel logs by correlation ID.';

    public function handle(): int
    {
        $correlationId = strtoupper(trim((string) $this->argument('correlationId')));
        if (preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $correlationId) !== 1) {
            $this->error('The correlation ID must be a valid 26-character ULID.');

            return self::INVALID;
        }

        $logDirectory = storage_path('logs');
        if (! is_dir($logDirectory)) {
            $this->error('Laravel log directory is unavailable.');

            return self::FAILURE;
        }

        $matches = [];
        $files = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($logDirectory)),
            '/\.log$/i',
        );
        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $handle = fopen($file->getPathname(), 'rb');
            if ($handle === false) {
                continue;
            }
            $lineNumber = 0;
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                if (str_contains(strtoupper($line), $correlationId)) {
                    $matches[] = [$file->getFilename(), $lineNumber, trim($line)];
                }
            }
            fclose($handle);
        }

        if ($matches === []) {
            $this->warn('No local log entry matched the supplied support reference.');

            return self::FAILURE;
        }

        $this->table(['Log', 'Line', 'Entry'], $matches);

        return self::SUCCESS;
    }
}
