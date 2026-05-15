<?php

namespace Modules\Document\Application\Actions;

use Modules\Document\Domain\Services\DocumentDomainService;

class CalculateItemTotalAction
{
    public function __construct(
        private readonly DocumentDomainService $domainService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload, string $itemType, int $tenantId): string
    {
        return $this->domainService->calculateItemTotal($payload, $itemType, $tenantId);
    }
}
