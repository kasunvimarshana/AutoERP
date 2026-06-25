<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Documents\Scanning;

use Modules\Tenant\Data\TenantDocumentScanResult;

interface TenantDocumentScannerInterface
{
    public function scan(string $filePath): TenantDocumentScanResult;
}
