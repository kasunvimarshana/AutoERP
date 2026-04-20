<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\DeleteArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\FindArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\ReconcileArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UpdateArTransactionServiceInterface;
use Modules\Finance\Domain\Entities\ArTransaction;
use Modules\Finance\Infrastructure\Http\Requests\ListArTransactionRequest;
use Modules\Finance\Infrastructure\Http\Requests\ReconcileArTransactionRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreArTransactionRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateArTransactionRequest;
use Modules\Finance\Infrastructure\Http\Resources\ArTransactionCollection;
use Modules\Finance\Infrastructure\Http\Resources\ArTransactionResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArTransactionController extends AuthorizedController
{
    public function __construct(
        private readonly CreateArTransactionServiceInterface $createService,
        private readonly UpdateArTransactionServiceInterface $updateService,
        private readonly DeleteArTransactionServiceInterface $deleteService,
        private readonly FindArTransactionServiceInterface $findService,
        private readonly ReconcileArTransactionServiceInterface $reconcileService,
    ) {}

    public function index(ListArTransactionRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ArTransaction::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'transaction_type' => $validated['transaction_type'] ?? null,
            'is_reconciled' => $validated['is_reconciled'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new ArTransactionCollection($items))->response();
    }

    public function store(StoreArTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', ArTransaction::class);

        $ar = $this->createService->execute($request->validated());

        return (new ArTransactionResource($ar))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $arTransaction): ArTransactionResource
    {
        $found = $this->findOrFail($arTransaction);
        $this->authorize('view', $found);

        return new ArTransactionResource($found);
    }

    public function update(UpdateArTransactionRequest $request, int $arTransaction): ArTransactionResource
    {
        $found = $this->findOrFail($arTransaction);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $arTransaction;

        return new ArTransactionResource($this->updateService->execute($payload));
    }

    public function destroy(int $arTransaction): JsonResponse
    {
        $found = $this->findOrFail($arTransaction);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $arTransaction]);

        return Response::json(['message' => 'AR transaction deleted successfully']);
    }

    public function reconcile(ReconcileArTransactionRequest $request, int $arTransaction): ArTransactionResource
    {
        $found = $this->findOrFail($arTransaction);
        $this->authorize('update', $found);

        return new ArTransactionResource($this->reconcileService->execute(['id' => $arTransaction]));
    }

    private function findOrFail(int $id): ArTransaction
    {
        $ar = $this->findService->find($id);

        if (! $ar) {
            throw new NotFoundHttpException('AR transaction not found.');
        }

        return $ar;
    }
}
