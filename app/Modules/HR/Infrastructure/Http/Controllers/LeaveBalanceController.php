<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\FindLeaveBalanceServiceInterface;
use Modules\HR\Domain\Entities\LeaveBalance;
use Modules\HR\Infrastructure\Http\Resources\LeaveBalanceResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LeaveBalanceController extends AuthorizedController
{
    public function __construct(
        protected FindLeaveBalanceServiceInterface $findService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', LeaveBalance::class);
        $result = $this->findService->list();

        return Response::json(['data' => LeaveBalanceResource::collection($result)]);
    }

    public function show(int $leaveBalance): LeaveBalanceResource
    {
        $entity = $this->findOrFail($leaveBalance);
        $this->authorize('view', $entity);

        return new LeaveBalanceResource($entity);
    }

    private function findOrFail(int $id): LeaveBalance
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Leave balance not found.');
        }

        return $entity;
    }
}
