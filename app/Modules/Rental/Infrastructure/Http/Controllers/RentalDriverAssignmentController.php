<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\AssignDriverServiceInterface;
use Modules\Rental\Application\Contracts\SubstituteDriverServiceInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDriverAssignmentRepositoryInterface;
use Modules\Rental\Infrastructure\Http\Requests\AssignDriverRequest;
use Modules\Rental\Infrastructure\Http\Requests\SubstituteDriverRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalDriverAssignmentResource;

class RentalDriverAssignmentController extends AuthorizedController
{
    public function __construct(
        private readonly RentalDriverAssignmentRepositoryInterface $assignmentRepository,
        private readonly AssignDriverServiceInterface $assignService,
        private readonly SubstituteDriverServiceInterface $substituteService,
    ) {}

    public function index(int $bookingId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $status = request()->string('status')->toString() ?: null;

        $assignments = $this->assignmentRepository->findByBooking($tenantId, $bookingId, $status);

        return RentalDriverAssignmentResource::collection($assignments);
    }

    public function show(int $bookingId, int $id): RentalDriverAssignmentResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $assignment = $this->assignmentRepository->findById($tenantId, $id);

        abort_if($assignment === null || $assignment->getRentalBookingId() !== $bookingId, 404, 'Driver assignment not found.');

        return new RentalDriverAssignmentResource($assignment);
    }

    public function store(AssignDriverRequest $request, int $bookingId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'rental_booking_id' => $bookingId,
            'assigned_by' => $request->user()?->id,
            'changed_by' => $request->user()?->id,
        ]);

        return (new RentalDriverAssignmentResource($this->assignService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function substitute(SubstituteDriverRequest $request, int $bookingId, int $id): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'original_assignment_id' => $id,
            'assigned_by' => $request->user()?->id,
            'changed_by' => $request->user()?->id,
        ]);

        return (new RentalDriverAssignmentResource($this->substituteService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(int $bookingId, int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $deleted = $this->assignmentRepository->delete($tenantId, $id);

        abort_if(! $deleted, 404, 'Driver assignment not found.');

        return response()->noContent();
    }
}
