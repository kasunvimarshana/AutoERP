<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Driver\Application\Contracts\DriverLicenseServiceInterface;
use Modules\Driver\Application\DTOs\CreateDriverLicenseDTO;
use Modules\Driver\Domain\Exceptions\DriverNotFoundException;
use Modules\Driver\Infrastructure\Http\Requests\CreateDriverLicenseRequest;
use Illuminate\Http\Request;

class DriverLicenseController extends Controller
{
    public function __construct(
        private readonly DriverLicenseServiceInterface $service,
    ) {}

    public function index(int $driverId): JsonResponse
    {
        $licenses = $this->service->listByDriver($driverId);

        return response()->json(['data' => array_map(fn ($l) => $this->toArray($l), $licenses)]);
    }

    public function expiringSoon(Request $request): JsonResponse
    {
        $tenantId  = (int) $request->header('X-Tenant-ID');
        $daysAhead = $request->query('days_ahead') ? (int) $request->query('days_ahead') : 30;
        $licenses  = $this->service->listExpiringSoon($tenantId, $daysAhead);

        return response()->json(['data' => array_map(fn ($l) => $this->toArray($l), $licenses)]);
    }

    public function store(CreateDriverLicenseRequest $request, int $driverId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v        = $request->validated();

        $dto = new CreateDriverLicenseDTO(
            tenantId:       $tenantId,
            driverId:       $driverId,
            licenseNumber:  $v['license_number'],
            licenseClass:   $v['license_class'],
            issuedCountry:  $v['issued_country'] ?? null,
            issueDate:      $v['issue_date'] ?? null,
            expiryDate:     $v['expiry_date'] ?? null,
            filePath:       $v['file_path'] ?? null,
        );

        $license = $this->service->create($dto);

        return response()->json(['data' => $this->toArray($license)], 201);
    }

    public function show(int $driverId, int $id): JsonResponse
    {
        try {
            $license = $this->service->getById($id);
        } catch (DriverNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->toArray($license)]);
    }

    public function update(CreateDriverLicenseRequest $request, int $driverId, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->header('X-Tenant-ID');
            $v        = $request->validated();

            $dto = new CreateDriverLicenseDTO(
                tenantId:       $tenantId,
                driverId:       $driverId,
                licenseNumber:  $v['license_number'],
                licenseClass:   $v['license_class'],
                issuedCountry:  $v['issued_country'] ?? null,
                issueDate:      $v['issue_date'] ?? null,
                expiryDate:     $v['expiry_date'] ?? null,
                filePath:       $v['file_path'] ?? null,
            );

            $license = $this->service->update($id, $dto);
        } catch (DriverNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->toArray($license)]);
    }

    public function destroy(int $driverId, int $id): JsonResponse
    {
        try {
            $this->service->delete($id);
        } catch (DriverNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(null, 204);
    }

    private function toArray(object $license): array
    {
        return [
            'id'             => $license->id,
            'tenant_id'      => $license->tenantId,
            'driver_id'      => $license->driverId,
            'license_number' => $license->licenseNumber,
            'license_class'  => $license->licenseClass,
            'issued_country' => $license->issuedCountry,
            'issue_date'     => $license->issueDate,
            'expiry_date'    => $license->expiryDate,
            'file_path'      => $license->filePath,
            'is_active'      => $license->isActive,
        ];
    }
}
