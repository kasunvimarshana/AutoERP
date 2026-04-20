<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\DeleteBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\FindBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\UpdateBankAccountServiceInterface;
use Modules\Finance\Domain\Entities\BankAccount;
use Modules\Finance\Infrastructure\Http\Requests\ListBankAccountRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreBankAccountRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateBankAccountRequest;
use Modules\Finance\Infrastructure\Http\Resources\BankAccountCollection;
use Modules\Finance\Infrastructure\Http\Resources\BankAccountResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BankAccountController extends AuthorizedController
{
    public function __construct(
        private readonly CreateBankAccountServiceInterface $createService,
        private readonly UpdateBankAccountServiceInterface $updateService,
        private readonly DeleteBankAccountServiceInterface $deleteService,
        private readonly FindBankAccountServiceInterface $findService,
    ) {}

    public function index(ListBankAccountRequest $request): JsonResponse
    {
        $this->authorize('viewAny', BankAccount::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new BankAccountCollection($items))->response();
    }

    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        $this->authorize('create', BankAccount::class);

        $bankAccount = $this->createService->execute($request->validated());

        return (new BankAccountResource($bankAccount))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $bankAccount): BankAccountResource
    {
        $found = $this->findOrFail($bankAccount);
        $this->authorize('view', $found);

        return new BankAccountResource($found);
    }

    public function update(UpdateBankAccountRequest $request, int $bankAccount): BankAccountResource
    {
        $found = $this->findOrFail($bankAccount);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $bankAccount;

        return new BankAccountResource($this->updateService->execute($payload));
    }

    public function destroy(int $bankAccount): JsonResponse
    {
        $found = $this->findOrFail($bankAccount);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $bankAccount]);

        return Response::json(['message' => 'Bank account deleted successfully']);
    }

    private function findOrFail(int $id): BankAccount
    {
        $ba = $this->findService->find($id);

        if (! $ba) {
            throw new NotFoundHttpException('Bank account not found.');
        }

        return $ba;
    }
}
