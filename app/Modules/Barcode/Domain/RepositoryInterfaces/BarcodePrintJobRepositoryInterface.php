<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\RepositoryInterfaces;

use Modules\Barcode\Domain\Entities\BarcodePrintJob;

interface BarcodePrintJobRepositoryInterface
{
    public function findById(int $id): ?BarcodePrintJob;

    /** @return BarcodePrintJob[] */
    public function findAll(int $tenantId): array;

    /** @return BarcodePrintJob[] */
    public function findByStatus(int $tenantId, string $status): array;

    /** @return BarcodePrintJob[] */
    public function findByDefinition(int $tenantId, int $barcodeDefinitionId): array;

    public function save(BarcodePrintJob $job): BarcodePrintJob;

    public function delete(int $id): void;
}
