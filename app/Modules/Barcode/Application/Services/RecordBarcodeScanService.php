<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Services;

use Modules\Barcode\Application\Contracts\RecordBarcodeScanServiceInterface;
use Modules\Barcode\Domain\Entities\BarcodeScan;
use Modules\Barcode\Domain\Events\BarcodeScanRecorded;
use Modules\Barcode\Domain\Exceptions\BarcodeNotFoundException;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeDefinitionRepositoryInterface;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeScanRepositoryInterface;

class RecordBarcodeScanService implements RecordBarcodeScanServiceInterface
{
    public function __construct(
        private readonly BarcodeScanRepositoryInterface       $scans,
        private readonly BarcodeDefinitionRepositoryInterface $definitions,
    ) {}

    public function record(
        int $tenantId,
        string $scannedValue,
        ?int $scannedByUserId,
        ?string $deviceId,
        ?string $locationTag,
        array $metadata = [],
    ): BarcodeScan {
        $definition = $this->definitions->findByValue($tenantId, $scannedValue);

        $barcodeDefinitionId = $definition?->getId();
        $resolvedType        = $definition?->getType();

        $scan = new BarcodeScan(
            null,
            $tenantId,
            $barcodeDefinitionId,
            $scannedValue,
            $resolvedType,
            $scannedByUserId,
            $deviceId,
            $locationTag,
            $metadata,
            new \DateTime(),
        );

        $saved = $this->scans->save($scan);

        if (function_exists('app') && app()->bound('events')) {
            event(new BarcodeScanRecorded($saved));
        }

        return $saved;
    }

    public function getById(int $id): BarcodeScan
    {
        $scan = $this->scans->findById($id);

        if ($scan === null) {
            throw BarcodeNotFoundException::withId($id);
        }

        return $scan;
    }

    /** @return BarcodeScan[] */
    public function getByDefinition(int $tenantId, int $barcodeDefinitionId): array
    {
        return $this->scans->findByDefinition($tenantId, $barcodeDefinitionId);
    }

    /** @return BarcodeScan[] */
    public function getByDateRange(int $tenantId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->scans->findByDateRange($tenantId, $from, $to);
    }

    public function delete(int $id): void
    {
        $this->getById($id); // throws if not found
        $this->scans->delete($id);
    }
}
