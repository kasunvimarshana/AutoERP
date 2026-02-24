<?php

namespace Modules\QualityControl\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\QualityControl\Domain\Contracts\InspectionRepositoryInterface;

class CreateInspectionUseCase
{
    public function __construct(
        private InspectionRepositoryInterface $inspectionRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];

            $referenceNo = $this->generateReferenceNo($tenantId);

            return $this->inspectionRepo->create([
                'tenant_id'        => $tenantId,
                'quality_point_id' => $data['quality_point_id'] ?? null,
                'reference_no'     => $referenceNo,
                'product_id'       => $data['product_id'] ?? null,
                'lot_number'       => $data['lot_number'] ?? null,
                'qty_inspected'    => $data['qty_inspected'] ?? '0.00000000',
                'qty_failed'       => '0.00000000',
                'status'           => 'draft',
                'inspector_id'     => $data['inspector_id'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'inspected_at'     => null,
            ]);
        });
    }

    private function generateReferenceNo(string $tenantId): string
    {
        $year  = now()->year;
        $count = DB::table('qc_inspections')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count();

        $sequence = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);

        return "QI-{$year}-{$sequence}";
    }
}
