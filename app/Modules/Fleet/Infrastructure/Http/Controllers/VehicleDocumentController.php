<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Fleet\Application\Contracts\VehicleDocumentServiceInterface;
use Modules\Fleet\Application\DTOs\CreateVehicleDocumentDTO;
use Modules\Fleet\Infrastructure\Http\Requests\CreateVehicleDocumentRequest;

class VehicleDocumentController extends Controller
{
    public function __construct(
        private readonly VehicleDocumentServiceInterface $service,
    ) {}

    public function index(int $vehicleId): JsonResponse
    {
        $docs = $this->service->listByVehicle($vehicleId);

        return response()->json(['data' => array_map(fn ($d) => [
            'id'            => $d->id,
            'document_type' => $d->documentType,
            'expiry_date'   => $d->expiryDate,
            'is_active'     => $d->isActive,
        ], $docs)]);
    }

    public function store(CreateVehicleDocumentRequest $request, int $vehicleId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v = $request->validated();

        $dto = new CreateVehicleDocumentDTO(
            tenantId:         $tenantId,
            vehicleId:        $vehicleId,
            documentType:     $v['document_type'],
            documentNumber:   $v['document_number'] ?? null,
            issuingAuthority: $v['issuing_authority'] ?? null,
            issueDate:        $v['issue_date'] ?? null,
            expiryDate:       $v['expiry_date'] ?? null,
            filePath:         $v['file_path'] ?? null,
            notes:            $v['notes'] ?? null,
            isActive:         $v['is_active'] ?? true,
        );

        $doc = $this->service->create($dto);

        return response()->json(['data' => ['id' => $doc->id]], 201);
    }

    public function show(int $vehicleId, int $id): JsonResponse
    {
        $doc = $this->service->find($id);

        if ($doc === null || $doc->vehicleId !== $vehicleId) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        return response()->json(['data' => [
            'id'                => $doc->id,
            'document_type'     => $doc->documentType,
            'document_number'   => $doc->documentNumber,
            'issuing_authority' => $doc->issuingAuthority,
            'issue_date'        => $doc->issueDate,
            'expiry_date'       => $doc->expiryDate,
            'notes'             => $doc->notes,
            'is_active'         => $doc->isActive,
        ]]);
    }

    public function update(Request $request, int $vehicleId, int $id): JsonResponse
    {
        $validated = $request->validate([
            'document_number'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'issuing_authority' => ['sometimes', 'nullable', 'string', 'max:150'],
            'issue_date'        => ['sometimes', 'nullable', 'date'],
            'expiry_date'       => ['sometimes', 'nullable', 'date'],
            'file_path'         => ['sometimes', 'nullable', 'string', 'max:500'],
            'notes'             => ['sometimes', 'nullable', 'string'],
            'is_active'         => ['sometimes', 'boolean'],
        ]);

        $doc = $this->service->update($id, $validated);

        return response()->json(['data' => ['id' => $doc->id]]);
    }

    public function destroy(int $vehicleId, int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    public function expiringSoon(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $days = (int) ($request->query('days', 30));
        $docs = $this->service->listExpiringSoon($tenantId, $days);

        return response()->json(['data' => array_map(fn ($d) => [
            'id'            => $d->id,
            'vehicle_id'    => $d->vehicleId,
            'document_type' => $d->documentType,
            'expiry_date'   => $d->expiryDate,
        ], $docs)]);
    }
}
