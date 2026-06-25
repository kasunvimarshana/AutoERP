<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Documents\Scanning;

use InvalidArgumentException;
use Modules\Tenant\Data\TenantDocumentScanResult;

/**
 * Explicit local/test implementation. Production must use a real scanner.
 */
final class TrustedLocalTenantDocumentScanner implements TenantDocumentScannerInterface
{
    public function scan(string $filePath): TenantDocumentScanResult
    {
        if ($filePath === '' || ! is_file($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException('The uploaded document cannot be scanned.');
        }

        return new TenantDocumentScanResult(true, 'trusted-local');
    }
}
