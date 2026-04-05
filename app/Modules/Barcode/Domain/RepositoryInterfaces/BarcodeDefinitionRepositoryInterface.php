<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\RepositoryInterfaces;

use Modules\Barcode\Domain\Entities\BarcodeDefinition;

interface BarcodeDefinitionRepositoryInterface
{
    public function findById(int $id): ?BarcodeDefinition;

    public function findByValue(int $tenantId, string $value): ?BarcodeDefinition;

    /** @return BarcodeDefinition[] */
    public function findByEntity(int $tenantId, string $entityType, int $entityId): array;

    /** @return BarcodeDefinition[] */
    public function findAll(int $tenantId): array;

    public function save(BarcodeDefinition $barcodeDefinition): BarcodeDefinition;

    public function delete(int $id): void;
}
