<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Documents\Scanning;

use InvalidArgumentException;
use Modules\Tenant\Data\TenantDocumentScanResult;
use RuntimeException;

final class ClamAvTenantDocumentScanner implements TenantDocumentScannerInterface
{
    private const CHUNK_BYTES = 8192;
    private const CLEAN_SUFFIX = ' OK';
    private const INFECTED_SUFFIX = ' FOUND';

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly float $timeoutSeconds,
    ) {}

    public function scan(string $filePath): TenantDocumentScanResult
    {
        if ($filePath === '' || ! is_file($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException('The uploaded document cannot be scanned.');
        }

        $socket = @stream_socket_client(
            sprintf('tcp://%s:%d', $this->host, $this->port),
            $errorNumber,
            $errorMessage,
            $this->timeoutSeconds,
        );
        if (! is_resource($socket)) {
            throw new RuntimeException('The document security scanner is unavailable.');
        }

        $seconds = max(1, (int) ceil($this->timeoutSeconds));
        stream_set_timeout($socket, $seconds);

        $handle = fopen($filePath, 'rb');
        if (! is_resource($handle)) {
            fclose($socket);
            throw new RuntimeException('The uploaded document cannot be opened for scanning.');
        }

        try {
            $this->writeAll($socket, "zINSTREAM\0");
            while (! feof($handle)) {
                $chunk = fread($handle, self::CHUNK_BYTES);
                if ($chunk === false) {
                    throw new RuntimeException('The uploaded document could not be read during scanning.');
                }
                if ($chunk === '') {
                    continue;
                }

                $this->writeAll($socket, pack('N', strlen($chunk)).$chunk);
            }
            $this->writeAll($socket, pack('N', 0));

            $response = stream_get_contents($socket);
            if (! is_string($response) || trim($response) === '') {
                throw new RuntimeException('The document security scanner returned no result.');
            }

            $response = trim($response, "\0\r\n ");
            if (str_ends_with($response, self::CLEAN_SUFFIX)) {
                return new TenantDocumentScanResult(true, 'clamav');
            }

            if (str_ends_with($response, self::INFECTED_SUFFIX)) {
                $separator = strrpos($response, ': ');
                $signature = $separator === false
                    ? 'malware'
                    : trim(substr($response, $separator + 2, -strlen(self::INFECTED_SUFFIX)));

                return new TenantDocumentScanResult(false, 'clamav', $signature !== '' ? $signature : 'malware');
            }

            throw new RuntimeException('The document security scanner could not classify the file.');
        } finally {
            fclose($handle);
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function writeAll($socket, string $payload): void
    {
        $offset = 0;
        $length = strlen($payload);
        while ($offset < $length) {
            $written = fwrite($socket, substr($payload, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Communication with the document security scanner failed.');
            }
            $offset += $written;
        }
    }
}
