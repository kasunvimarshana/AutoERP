<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\RepositoryInterfaces;

use Modules\Barcode\Domain\Entities\BarcodeScan;

interface BarcodeScanRepositoryInterface
{
    public function findById(int $id): ?BarcodeScan;

    /** @return BarcodeScan[] */
    public function findByDefinition(int $tenantId, int $barcodeDefinitionId): array;

    /** @return BarcodeScan[] */
    public function findByDateRange(int $tenantId, \DateTimeInterface $from, \DateTimeInterface $to): array;

    public function save(BarcodeScan $barcodeScan): BarcodeScan;

    public function delete(int $id): void;
}
