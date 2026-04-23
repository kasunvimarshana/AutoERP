<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\ApproveLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CancelLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\RejectLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\SubmitLeaveRequestServiceInterface;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Infrastructure\Http\Requests\ApproveLeaveRequestRequest;
use Modules\HR\Infrastructure\Http\Requests\RejectLeaveRequestRequest;
use Modules\HR\Infrastructure\Http\Requests\StoreLeaveRequestRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateLeaveRequestRequest;
use Modules\HR\Infrastructure\Http\Resources\LeaveRequestResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LeaveRequestController extends AuthorizedController
{
    public function __construct(
        protected SubmitLeaveRequestServiceInterface $submitService,
        protected FindLeaveRequestServiceInterface $findService,
        protected ApproveLeaveRequestServiceInterface $approveService,
        protected RejectLeaveRequestServiceInterface $rejectService,
        protected CancelLeaveRequestServiceInterface $cancelService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->findService->list();

        return Response::json(['data' => LeaveRequestResource::collection($result)]);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $entity = $this->submitService->execute($request->validated());

        return (new LeaveRequestResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $leaveRequest): LeaveRequestResource
    {
        return new LeaveRequestResource($this->findOrFail($leaveRequest));
    }

    public function update(UpdateLeaveRequestRequest $request, int $leaveRequest): LeaveRequestResource
    {
        $this->findOrFail($leaveRequest);
        $payload = $request->validated();
        $payload['id'] = $leaveRequest;
        $updated = $this->submitService->execute($payload);

        return new LeaveRequestResource($updated);
    }

    public function destroy(int $leaveRequest): JsonResponse
    {
        $this->findOrFail($leaveRequest);
        $this->cancelService->execute(['id' => $leaveRequest]);

        return Response::json(null, 204);
    }

    public function approve(ApproveLeaveRequestRequest $request, int $leaveRequest): LeaveRequestResource
    {
        $this->findOrFail($leaveRequest);
        $payload = $request->validated();
        $payload['id'] = $leaveRequest;
        $updated = $this->approveService->execute($payload);

        return new LeaveRequestResource($updated);
    }

    public function reject(RejectLeaveRequestRequest $request, int $leaveRequest): LeaveRequestResource
    {
        $this->findOrFail($leaveRequest);
        $payload = $request->validated();
        $payload['id'] = $leaveRequest;
        $updated = $this->rejectService->execute($payload);

        return new LeaveRequestResource($updated);
    }

    public function cancel(int $leaveRequest): JsonResponse
    {
        $this->findOrFail($leaveRequest);
        $this->cancelService->execute(['id' => $leaveRequest]);

        return Response::json(['message' => 'Leave request cancelled.']);
    }

    private function findOrFail(int $id): LeaveRequest
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Leave request not found.');
        }

        return $entity;
    }
}
