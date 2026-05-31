<?php

declare(strict_types=1);

namespace Modules\Core\Application\Repositories;

use Modules\Core\Application\Contracts\RepositoryPortInterface;
use Modules\Core\Application\DTO\DataRecord;

interface BusinessPartyLinkRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listForSource(int $tenantId, string $sourcePartyType, ?int $sourcePartyId): array;

    /**
     * @return list<DataRecord>
     */
    public function listForTarget(int $tenantId, string $targetPartyType, ?int $targetPartyId): array;

    /**
     * @return list<DataRecord>
     */
    public function listForSourceAndTarget(
        int $tenantId,
        string $sourcePartyType,
        ?int $sourcePartyId,
        string $targetPartyType,
        ?int $targetPartyId,
    ): array;

    public function findInTenant(int $tenantId, int $linkId): ?DataRecord;

    public function activeDuplicateExists(
        int $tenantId,
        string $sourcePartyType,
        ?int $sourcePartyId,
        string $targetPartyType,
        ?int $targetPartyId,
        string $relationType,
        ?int $exceptLinkId = null,
    ): bool;

    public function partyReferenceExists(string $partyType, int $partyId, int $tenantId): bool;
}
