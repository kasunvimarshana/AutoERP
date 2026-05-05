<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Rental\Application\Contracts\ManageRentalReservationServiceInterface;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalReservationRequest;
use Modules\Rental\Infrastructure\Http\Requests\UpdateRentalReservationRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalReservationResource;

class RentalReservationController extends Controller
{
    public function __construct(
        private readonly ManageRentalReservationServiceInterface $service,
    ) {}

    public function create(CreateRentalReservationRequest $request): JsonResponse
    {
        $reservation = $this->service->create($request->validated());
        return response()->json(new RentalReservationResource($reservation), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $result = $this->service->list($tenantId, (int) ($request->query('per_page', 15)), (int) ($request->query('page', 1)));
        return response()->json($result);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $reservation = $this->service->find($tenantId, $id);
        return response()->json(new RentalReservationResource($reservation));
    }

    public function update(UpdateRentalReservationRequest $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $reservation = $this->service->update($tenantId, $id, $request->validated());
        return response()->json(new RentalReservationResource($reservation));
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $reservation = $this->service->confirm($tenantId, $id);
        return response()->json(new RentalReservationResource($reservation));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $reservation = $this->service->cancel($tenantId, $id);
        return response()->json(new RentalReservationResource($reservation));
    }
}
