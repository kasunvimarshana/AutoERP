<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CategorizeBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\DeleteBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\FindBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UpdateBankTransactionServiceInterface;
use Modules\Finance\Domain\Entities\BankTransaction;
use Modules\Finance\Infrastructure\Http\Requests\CategorizeBankTransactionRequest;
use Modules\Finance\Infrastructure\Http\Requests\ListBankTransactionRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreBankTransactionRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateBankTransactionRequest;
use Modules\Finance\Infrastructure\Http\Resources\BankTransactionCollection;
use Modules\Finance\Infrastructure\Http\Resources\BankTransactionResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BankTransactionController extends AuthorizedController
{
    public function __construct(
        private readonly CreateBankTransactionServiceInterface $createService,
        private readonly UpdateBankTransactionServiceInterface $updateService,
        private readonly DeleteBankTransactionServiceInterface $deleteService,
        private readonly FindBankTransactionServiceInterface $findService,
        private readonly CategorizeBankTransactionServiceInterface $categorizeService,
    ) {}

    public function index(ListBankTransactionRequest $request): JsonResponse
    {
        $this->authorize('viewAny', BankTransaction::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new BankTransactionCollection($items))->response();
    }

    public function store(StoreBankTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', BankTransaction::class);

        $bt = $this->createService->execute($request->validated());

        return (new BankTransactionResource($bt))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $bankTransaction): BankTransactionResource
    {
        $found = $this->findOrFail($bankTransaction);
        $this->authorize('view', $found);

        return new BankTransactionResource($found);
    }

    public function update(UpdateBankTransactionRequest $request, int $bankTransaction): BankTransactionResource
    {
        $found = $this->findOrFail($bankTransaction);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $bankTransaction;

        return new BankTransactionResource($this->updateService->execute($payload));
    }

    public function destroy(int $bankTransaction): JsonResponse
    {
        $found = $this->findOrFail($bankTransaction);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $bankTransaction]);

        return Response::json(['message' => 'Bank transaction deleted successfully']);
    }

    public function categorize(CategorizeBankTransactionRequest $request, int $bankTransaction): BankTransactionResource
    {
        $found = $this->findOrFail($bankTransaction);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $bankTransaction;

        return new BankTransactionResource($this->categorizeService->execute($payload));
    }

    private function findOrFail(int $id): BankTransaction
    {
        $bt = $this->findService->find($id);

        if (! $bt) {
            throw new NotFoundHttpException('Bank transaction not found.');
        }

        return $bt;
    }
}
