<?php

namespace Modules\QualityControl\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\QualityControl\Domain\Contracts\InspectionRepositoryInterface;
use Modules\QualityControl\Domain\Events\InspectionFailed;

class FailInspectionUseCase
{
    public function __construct(
        private InspectionRepositoryInterface $inspectionRepo,
    ) {}

    public function execute(string $inspectionId, array $data = []): object
    {
        return DB::transaction(function () use ($inspectionId, $data) {
            $inspection = $this->inspectionRepo->findById($inspectionId);

            if (! $inspection) {
                throw new DomainException('Inspection not found.');
            }

            if (! in_array($inspection->status, ['draft', 'in_progress'], true)) {
                throw new DomainException('Only draft or in-progress inspections can be failed.');
            }

            $qtyInspected = $data['qty_inspected'] ?? $inspection->qty_inspected;
            $qtyFailed    = $data['qty_failed'] ?? '0.00000000';

            if (bccomp((string) $qtyFailed, (string) $qtyInspected, 8) > 0) {
                throw new DomainException('Failed quantity cannot exceed inspected quantity.');
            }

            $updated = $this->inspectionRepo->update($inspectionId, [
                'status'       => 'failed',
                'qty_inspected' => $qtyInspected,
                'qty_failed'   => $qtyFailed,
                'notes'        => $data['notes'] ?? $inspection->notes,
                'inspected_at' => now(),
            ]);

            Event::dispatch(new InspectionFailed(
                inspectionId: $inspectionId,
                tenantId:     $inspection->tenant_id,
                title:        (string) ($data['title'] ?? ('Failed inspection ' . ($inspection->reference_no ?? $inspectionId))),
                productId:    (string) ($inspection->product_id ?? ''),
                priority:     (string) ($data['priority'] ?? 'medium'),
                equipmentId:  (string) ($data['equipment_id'] ?? ''),
            ));

            return $updated;
        });
    }
}
