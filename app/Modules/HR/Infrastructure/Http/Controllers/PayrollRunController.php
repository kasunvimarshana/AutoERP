<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\ApprovePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\CreatePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\FindPayrollRunServiceInterface;
use Modules\HR\Application\Contracts\ProcessPayrollRunServiceInterface;
use Modules\HR\Domain\Entities\PayrollRun;
use Modules\HR\Infrastructure\Http\Requests\ApprovePayrollRunRequest;
use Modules\HR\Infrastructure\Http\Requests\ProcessPayrollRunRequest;
use Modules\HR\Infrastructure\Http\Requests\StorePayrollRunRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdatePayrollRunRequest;
use Modules\HR\Infrastructure\Http\Resources\PayrollRunResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PayrollRunController extends AuthorizedController
{
    public function __construct(
        protected CreatePayrollRunServiceInterface $createService,
        protected FindPayrollRunServiceInterface $findService,
        protected ApprovePayrollRunServiceInterface $approveService,
        protected ProcessPayrollRunServiceInterface $processService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->findService->list();

        return Response::json(['data' => PayrollRunResource::collection($result)]);
    }

    public function store(StorePayrollRunRequest $request): JsonResponse
    {
        $entity = $this->createService->execute($request->validated());

        return (new PayrollRunResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $payrollRun): PayrollRunResource
    {
        return new PayrollRunResource($this->findOrFail($payrollRun));
    }

    public function update(UpdatePayrollRunRequest $request, int $payrollRun): PayrollRunResource
    {
        $this->findOrFail($payrollRun);
        $payload = $request->validated();
        $payload['id'] = $payrollRun;
        $updated = $this->createService->execute($payload);

        return new PayrollRunResource($updated);
    }

    public function destroy(int $payrollRun): JsonResponse
    {
        $this->findOrFail($payrollRun);

        return Response::json(null, 204);
    }

    public function approve(ApprovePayrollRunRequest $request, int $payrollRun): PayrollRunResource
    {
        $this->findOrFail($payrollRun);
        $payload = $request->validated();
        $payload['id'] = $payrollRun;
        $updated = $this->approveService->execute($payload);

        return new PayrollRunResource($updated);
    }

    public function process(ProcessPayrollRunRequest $request, int $payrollRun): PayrollRunResource
    {
        $this->findOrFail($payrollRun);
        $updated = $this->processService->execute(['id' => $payrollRun]);

        return new PayrollRunResource($updated);
    }

    private function findOrFail(int $id): PayrollRun
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Payroll run not found.');
        }

        return $entity;
    }
}
