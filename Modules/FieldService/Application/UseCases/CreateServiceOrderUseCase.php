<?php

namespace Modules\FieldService\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\FieldService\Domain\Contracts\ServiceOrderRepositoryInterface;

class CreateServiceOrderUseCase
{
    public function __construct(
        private ServiceOrderRepositoryInterface $orderRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId    = $data['tenant_id'];
            $referenceNo = $this->generateReferenceNo($tenantId);

            return $this->orderRepo->create([
                'tenant_id'       => $tenantId,
                'service_team_id' => $data['service_team_id'] ?? null,
                'reference_no'    => $referenceNo,
                'title'           => $data['title'],
                'description'     => $data['description'] ?? null,
                'customer_id'     => $data['customer_id'] ?? null,
                'contact_name'    => $data['contact_name'] ?? null,
                'contact_phone'   => $data['contact_phone'] ?? null,
                'location'        => $data['location'] ?? null,
                'technician_id'   => null,
                'status'          => 'new',
                'duration_hours'  => '0.00000000',
                'labor_cost'      => '0.00000000',
                'parts_cost'      => '0.00000000',
                'scheduled_at'    => $data['scheduled_at'] ?? null,
            ]);
        });
    }

    private function generateReferenceNo(string $tenantId): string
    {
        $year  = now()->year;
        $count = DB::table('fs_service_orders')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count();

        $sequence = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);

        return "FSO-{$year}-{$sequence}";
    }
}
