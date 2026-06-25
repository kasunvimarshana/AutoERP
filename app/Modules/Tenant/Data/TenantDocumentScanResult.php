<?php

declare(strict_types=1);

namespace Modules\Tenant\Data;

final readonly class TenantDocumentScanResult
{
    public function __construct(
        public bool $clean,
        public string $engine,
        public ?string $signature = null,
    ) {}
}
