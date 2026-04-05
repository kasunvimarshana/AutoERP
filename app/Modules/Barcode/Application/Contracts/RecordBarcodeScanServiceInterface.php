<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Contracts;

use Modules\Barcode\Domain\Entities\BarcodeScan;

interface RecordBarcodeScanServiceInterface
{
    public function record(
        int $tenantId,
        string $scannedValue,
        ?int $scannedByUserId,
        ?string $deviceId,
        ?string $locationTag,
        array $metadata = [],
    ): BarcodeScan;

    public function getById(int $id): BarcodeScan;

    /** @return BarcodeScan[] */
    public function getByDefinition(int $tenantId, int $barcodeDefinitionId): array;

    /** @return BarcodeScan[] */
    public function getByDateRange(int $tenantId, \DateTimeInterface $from, \DateTimeInterface $to): array;

    public function delete(int $id): void;
}
