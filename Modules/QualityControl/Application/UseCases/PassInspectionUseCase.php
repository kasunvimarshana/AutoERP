<?php

namespace Modules\QualityControl\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\QualityControl\Domain\Contracts\InspectionRepositoryInterface;
use Modules\QualityControl\Domain\Events\InspectionPassed;

class PassInspectionUseCase
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
                throw new DomainException('Only draft or in-progress inspections can be passed.');
            }

            $updated = $this->inspectionRepo->update($inspectionId, [
                'status'           => 'passed',
                'qty_inspected'    => $data['qty_inspected'] ?? $inspection->qty_inspected,
                'qty_failed'       => '0.00000000',
                'notes'            => $data['notes'] ?? $inspection->notes,
                'inspected_at'     => now(),
            ]);

            Event::dispatch(new InspectionPassed($inspectionId, $inspection->tenant_id));

            return $updated;
        });
    }
}
