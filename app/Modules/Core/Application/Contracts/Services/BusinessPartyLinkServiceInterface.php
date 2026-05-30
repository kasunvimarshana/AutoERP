<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface BusinessPartyLinkServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function list(array $filters): Result;

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     */
    public function update(int $linkId, array $payload): Result;

    public function deactivate(int $tenantId, int $linkId, ?string $endDate = null): Result;
}
