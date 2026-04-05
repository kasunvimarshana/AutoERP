<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Contracts;

use Modules\Barcode\Domain\Entities\BarcodeDefinition;

interface ManageBarcodeServiceInterface
{
    public function create(
        int $tenantId,
        string $type,
        string $value,
        ?string $label,
        ?string $entityType,
        ?int $entityId,
        array $metadata = [],
    ): BarcodeDefinition;

    public function getById(int $id): BarcodeDefinition;

    public function getByValue(int $tenantId, string $value): BarcodeDefinition;

    /** @return BarcodeDefinition[] */
    public function getForEntity(int $tenantId, string $entityType, int $entityId): array;

    /** @return BarcodeDefinition[] */
    public function listAll(int $tenantId): array;

    public function activate(int $id): BarcodeDefinition;

    public function deactivate(int $id): BarcodeDefinition;

    public function delete(int $id): void;
}
