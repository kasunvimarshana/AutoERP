<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Contracts;

use Modules\Barcode\Domain\Entities\BarcodePrintJob;

interface PrintBarcodeLabelServiceInterface
{
    /**
     * Queue a new print job for a barcode definition.
     *
     * @param  array<string,string> $variables  Extra placeholder substitution data
     */
    public function queue(
        int     $tenantId,
        int     $barcodeDefinitionId,
        ?int    $labelTemplateId,
        string  $format,
        ?string $printerTarget,
        int     $copies,
        array   $variables,
    ): BarcodePrintJob;

    /**
     * Render and complete a queued print job; returns the updated job.
     */
    public function process(int $jobId): BarcodePrintJob;

    public function getById(int $id): BarcodePrintJob;

    /** @return BarcodePrintJob[] */
    public function listAll(int $tenantId): array;

    /** @return BarcodePrintJob[] */
    public function listByStatus(int $tenantId, string $status): array;

    public function cancel(int $id): BarcodePrintJob;

    public function delete(int $id): void;
}
