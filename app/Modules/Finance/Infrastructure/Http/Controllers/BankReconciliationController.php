<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CompleteBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\CreateBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\DeleteBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\FindBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\UpdateBankReconciliationServiceInterface;
use Modules\Finance\Domain\Entities\BankReconciliation;
use Modules\Finance\Infrastructure\Http\Requests\CompleteBankReconciliationRequest;
use Modules\Finance\Infrastructure\Http\Requests\ListBankReconciliationRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreBankReconciliationRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateBankReconciliationRequest;
use Modules\Finance\Infrastructure\Http\Resources\BankReconciliationCollection;
use Modules\Finance\Infrastructure\Http\Resources\BankReconciliationResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BankReconciliationController extends AuthorizedController
{
    public function __construct(
        private readonly CreateBankReconciliationServiceInterface $createService,
        private readonly UpdateBankReconciliationServiceInterface $updateService,
        private readonly DeleteBankReconciliationServiceInterface $deleteService,
        private readonly FindBankReconciliationServiceInterface $findService,
        private readonly CompleteBankReconciliationServiceInterface $completeService,
    ) {}

    public function index(ListBankReconciliationRequest $request): JsonResponse
    {
        $this->authorize('viewAny', BankReconciliation::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new BankReconciliationCollection($items))->response();
    }

    public function store(StoreBankReconciliationRequest $request): JsonResponse
    {
        $this->authorize('create', BankReconciliation::class);

        $br = $this->createService->execute($request->validated());

        return (new BankReconciliationResource($br))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $bankReconciliation): BankReconciliationResource
    {
        $found = $this->findOrFail($bankReconciliation);
        $this->authorize('view', $found);

        return new BankReconciliationResource($found);
    }

    public function update(UpdateBankReconciliationRequest $request, int $bankReconciliation): BankReconciliationResource
    {
        $found = $this->findOrFail($bankReconciliation);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $bankReconciliation;

        return new BankReconciliationResource($this->updateService->execute($payload));
    }

    public function destroy(int $bankReconciliation): JsonResponse
    {
        $found = $this->findOrFail($bankReconciliation);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $bankReconciliation]);

        return Response::json(['message' => 'Bank reconciliation deleted successfully']);
    }

    public function complete(CompleteBankReconciliationRequest $request, int $bankReconciliation): BankReconciliationResource
    {
        $found = $this->findOrFail($bankReconciliation);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $bankReconciliation;

        return new BankReconciliationResource($this->completeService->execute($payload));
    }

    private function findOrFail(int $id): BankReconciliation
    {
        $br = $this->findService->find($id);

        if (! $br) {
            throw new NotFoundHttpException('Bank reconciliation not found.');
        }

        return $br;
    }
}
