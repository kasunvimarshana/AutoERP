<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\FindPayslipServiceInterface;
use Modules\HR\Domain\Entities\Payslip;
use Modules\HR\Infrastructure\Http\Resources\PayslipResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PayslipController extends AuthorizedController
{
    public function __construct(
        protected FindPayslipServiceInterface $findService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->findService->list();

        return Response::json(['data' => PayslipResource::collection($result)]);
    }

    public function show(int $payslip): PayslipResource
    {
        return new PayslipResource($this->findOrFail($payslip));
    }

    private function findOrFail(int $id): Payslip
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Payslip not found.');
        }

        return $entity;
    }
}
