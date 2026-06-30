<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\ApplicationKeyConfiguration;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

final class EnsureApplicationKeyCommand extends Command
{
    protected $signature = 'app:key:ensure';

    protected $description = 'Generate APP_KEY only when neither the environment file nor the runtime environment provides a valid key.';

    public function handle(): int
    {
        $path = $this->laravel->environmentFilePath();
        $contents = $this->readEnvironmentFile($path);
        $entries = ApplicationKeyConfiguration::values($contents);
        if (count($entries) > 1) {
            $this->error('The environment file contains multiple APP_KEY entries. Keep one explicit source of truth.');

            return self::FAILURE;
        }

        $cipher = (string) config('app.cipher');
        $fileValue = $entries[0] ?? null;
        $runtimeValue = trim((string) config('app.key'));
        if ($fileValue !== null && $fileValue !== '') {
            if (! ApplicationKeyConfiguration::isValid($fileValue, $cipher)) {
                $this->error('APP_KEY exists in the environment file but is invalid. Correct it explicitly; the existing value was not overwritten.');

                return self::FAILURE;
            }
            if (
                $runtimeValue !== ''
                && ! hash_equals(
                    ApplicationKeyConfiguration::decode($fileValue),
                    ApplicationKeyConfiguration::decode($runtimeValue),
                )
            ) {
                $this->error('Effective APP_KEY does not match the environment file. Clear cached configuration and remove conflicting process-level APP_KEY values.');

                return self::FAILURE;
            }

            $this->info('APP_KEY already exists in the environment file and is valid. No change was made.');

            return self::SUCCESS;
        }

        if ($runtimeValue !== '') {
            if (! ApplicationKeyConfiguration::isValid($runtimeValue, $cipher)) {
                $this->error('The runtime environment provides an invalid APP_KEY. Correct it explicitly; no key was generated.');

                return self::FAILURE;
            }

            $this->info('APP_KEY is supplied by the runtime environment. The environment file was not changed.');

            return self::SUCCESS;
        }

        $key = ApplicationKeyConfiguration::BASE64_PREFIX.base64_encode(Encrypter::generateKey($cipher));
        $updated = $this->writeApplicationKey($contents, $key);
        $written = file_put_contents($path, $updated, LOCK_EX);

        if ($written === false || $written !== strlen($updated)) {
            throw new RuntimeException(sprintf('Unable to write the application environment file [%s].', $path));
        }

        $this->info('APP_KEY was generated because no key source was configured.');

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
