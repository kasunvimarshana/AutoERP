<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Application\Contracts\VehicleDashboardServiceInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleDocumentRepositoryInterface;

class VehicleDashboardService implements VehicleDashboardServiceInterface
{
    public function __construct(
        private readonly VehicleDocumentRepositoryInterface $documentRepository,
    ) {}

    public function execute(array $data): array
    {
        $tenantId = (int) $data['tenant_id'];
        $expiryDays = (int) ($data['expiry_days'] ?? 30);

        $vehicles = DB::table('vehicles')->where('tenant_id', $tenantId)->whereNull('deleted_at');

        return [
            'totals' => [
                'all' => (clone $vehicles)->count(),
                'rental_available' => (clone $vehicles)->where('rental_status', 'available')->count(),
                'rented' => (clone $vehicles)->where('rental_status', 'rented')->count(),
                'in_service' => (clone $vehicles)->whereIn('service_status', ['in_maintenance', 'under_repair', 'awaiting_parts', 'quality_check'])->count(),
                'awaiting_parts' => (clone $vehicles)->where('service_status', 'awaiting_parts')->count(),
                'quality_control' => (clone $vehicles)->where('service_status', 'quality_check')->count(),
                'due_for_maintenance' => (clone $vehicles)->whereNotNull('next_maintenance_due_at')->where('next_maintenance_due_at', '<=', now())->count(),
            ],
            'expiring_documents' => $this->documentRepository->listExpiring($tenantId, $expiryDays),
        ];
    }
}
