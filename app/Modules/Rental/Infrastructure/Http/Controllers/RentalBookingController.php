<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\ActivateRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CancelRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CompleteRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalBookingServiceInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;
use Modules\Rental\Infrastructure\Http\Requests\CreateRentalBookingRequest;
use Modules\Rental\Infrastructure\Http\Requests\UpdateRentalBookingRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalBookingResource;

class RentalBookingController extends AuthorizedController
{
    public function __construct(
        private readonly RentalBookingRepositoryInterface $bookingRepository,
        private readonly CreateRentalBookingServiceInterface $createService,
        private readonly UpdateRentalBookingServiceInterface $updateService,
        private readonly ActivateRentalBookingServiceInterface $activateService,
        private readonly CompleteRentalBookingServiceInterface $completeService,
        private readonly CancelRentalBookingServiceInterface $cancelService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $orgUnitId = request()->integer('org_unit_id') ?: null;

        $bookings = $this->bookingRepository->findByTenant(
            $tenantId,
            $orgUnitId,
            request()->only(['status', 'customer_id']),
        );

        return RentalBookingResource::collection($bookings);
    }

    public function show(int $id): RentalBookingResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $booking = $this->bookingRepository->findById($tenantId, $id);

        abort_if($booking === null, 404, 'Rental booking not found.');

        return new RentalBookingResource($booking);
    }

    public function store(CreateRentalBookingRequest $request): RentalBookingResource
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'changed_by' => $request->user()?->id,
        ]);

        return new RentalBookingResource($this->createService->execute($data));
    }

    public function update(UpdateRentalBookingRequest $request, int $id): RentalBookingResource
    {
        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'changed_by' => $request->user()?->id,
        ]);

        return new RentalBookingResource($this->updateService->execute($data));
    }

    public function activate(int $id): RentalBookingResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $assetIds = (array) request()->input('asset_ids', []);

        return new RentalBookingResource($this->activateService->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'asset_ids' => $assetIds,
            'changed_by' => request()->user()?->id,
        ]));
    }

    public function complete(int $id): RentalBookingResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $assetIds = (array) request()->input('asset_ids', []);

        return new RentalBookingResource($this->completeService->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'asset_ids' => $assetIds,
            'actual_return_at' => request()->input('actual_return_at'),
            'final_amount' => request()->input('final_amount'),
            'security_deposit_status' => request()->input('security_deposit_status'),
            'notes' => request()->input('notes'),
            'changed_by' => request()->user()?->id,
        ]));
    }

    public function cancel(int $id): RentalBookingResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $assetIds = (array) request()->input('asset_ids', []);

        return new RentalBookingResource($this->cancelService->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'asset_ids' => $assetIds,
            'notes' => request()->input('notes'),
            'changed_by' => request()->user()?->id,
        ]));
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $this->bookingRepository->delete($tenantId, $id);

        return response()->json(null, 204);
    }
}
