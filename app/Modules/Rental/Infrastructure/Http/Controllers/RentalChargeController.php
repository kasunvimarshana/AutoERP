<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Rental\Application\Contracts\RentalChargeServiceInterface;
use Modules\Rental\Application\DTOs\CreateRentalChargeDTO;
use Modules\Rental\Domain\Exceptions\RentalNotFoundException;
use Modules\Rental\Domain\ValueObjects\ChargeType;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalChargeRequest;

class RentalChargeController extends Controller
{
    public function __construct(
        private readonly RentalChargeServiceInterface $service,
    ) {}

    public function index(Request $request, int $rentalId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $charges  = $this->service->listByRental($rentalId, $tenantId);

        return response()->json(['data' => array_map(fn ($c) => $this->toArray($c), $charges)]);
    }

    public function store(CreateRentalChargeRequest $request, int $rentalId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v        = $request->validated();

        try {
            $dto = new CreateRentalChargeDTO(
                tenantId:    $tenantId,
                rentalId:    $rentalId,
                chargeType:  ChargeType::from($v['charge_type']),
                description: $v['description'],
                quantity:    (string) $v['quantity'],
                unitPrice:   (string) $v['unit_price'],
            );
            $charge = $this->service->create($dto);
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->toArray($charge)], 201);
    }

    public function show(Request $request, int $rentalId, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        try {
            $charge = $this->service->getById($id, $tenantId);
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->toArray($charge)]);
    }

    public function destroy(Request $request, int $rentalId, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        try {
            $this->service->delete($id, $tenantId);
        } catch (RentalNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(null, 204);
    }

    private function toArray(object $charge): array
    {
        return [
            'id'          => $charge->id,
            'tenant_id'   => $charge->tenantId,
            'rental_id'   => $charge->rentalId,
            'charge_type' => $charge->chargeType->value,
            'description' => $charge->description,
            'quantity'    => $charge->quantity,
            'unit_price'  => $charge->unitPrice,
            'amount'      => $charge->amount,
            'is_active'   => $charge->isActive,
        ];
    }
}
