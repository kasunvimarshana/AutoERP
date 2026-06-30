<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

final class EnsureApplicationKeyCommand extends Command
{
    protected $signature = 'app:key:ensure';

    protected $description = 'Generate APP_KEY only when the application environment file does not already contain a valid key.';

    public function handle(): int
    {
        $path = $this->laravel->environmentFilePath();
        $contents = $this->readEnvironmentFile($path);
        $entries = $this->applicationKeyValues($contents);
        if (count($entries) > 1) {
            $this->error('The environment file contains multiple APP_KEY entries. Keep one explicit source of truth.');

            return self::FAILURE;
        }
        $currentValue = $entries[0] ?? null;

        if ($currentValue !== null && $currentValue !== '') {
            if (! $this->isValidKey($currentValue)) {
                $this->error('APP_KEY exists but is invalid. Correct it explicitly; the existing value was not overwritten.');

                return self::FAILURE;
            }

            $this->info('APP_KEY already exists and is valid. No change was made.');

            return self::SUCCESS;
        }

        $key = 'base64:'.base64_encode(Encrypter::generateKey((string) config('app.cipher')));
        $updated = $this->writeApplicationKey($contents, $key);
        $written = file_put_contents($path, $updated, LOCK_EX);

        if ($written === false || $written !== strlen($updated)) {
            throw new RuntimeException(sprintf('Unable to write the application environment file [%s].', $path));
        }

        $this->info('APP_KEY was generated because the environment file did not contain one.');

        return self::SUCCESS;
    }

    private function readEnvironmentFile(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException(sprintf('Application environment file [%s] is missing or unreadable.', $path));
        }

        $contents = file_get_contents($path);
        if (! is_string($contents)) {
            throw new RuntimeException(sprintf('Unable to read the application environment file [%s].', $path));
        }

        return $contents;
    }

    /** @return list<string> */
    private function applicationKeyValues(string $contents): array
    {
        preg_match_all('/^APP_KEY=(.*)$/m', $contents, $matches);

        return array_map(function (string $raw): string {
            $value = trim($raw);
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            return trim($value);
        }, $matches[1] ?? []);
    }

    private function isValidKey(string $value): bool
    {
        $key = str_starts_with($value, 'base64:')
            ? base64_decode(substr($value, 7), true)
            : $value;

        return is_string($key) && Encrypter::supported($key, (string) config('app.cipher'));
    }

    private function writeApplicationKey(string $contents, string $key): string
    {
        if (preg_match('/^APP_KEY=.*$/m', $contents) === 1) {
            $updated = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents, 1);

            if (! is_string($updated)) {
                throw new RuntimeException('Unable to update APP_KEY in the application environment file.');
            }

            return $updated;
        }

        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $prefix = rtrim($contents, "\r\n");

        return $prefix.$lineEnding.'APP_KEY='.$key.$lineEnding;
    }
}
