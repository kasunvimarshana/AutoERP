<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\DeleteApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\FindApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\ReconcileApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UpdateApTransactionServiceInterface;
use Modules\Finance\Domain\Entities\ApTransaction;
use Modules\Finance\Infrastructure\Http\Requests\ListApTransactionRequest;
use Modules\Finance\Infrastructure\Http\Requests\ReconcileApTransactionRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreApTransactionRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateApTransactionRequest;
use Modules\Finance\Infrastructure\Http\Resources\ApTransactionCollection;
use Modules\Finance\Infrastructure\Http\Resources\ApTransactionResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApTransactionController extends AuthorizedController
{
    public function __construct(
        private readonly CreateApTransactionServiceInterface $createService,
        private readonly UpdateApTransactionServiceInterface $updateService,
        private readonly DeleteApTransactionServiceInterface $deleteService,
        private readonly FindApTransactionServiceInterface $findService,
        private readonly ReconcileApTransactionServiceInterface $reconcileService,
    ) {}

    public function index(ListApTransactionRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ApTransaction::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'transaction_type' => $validated['transaction_type'] ?? null,
            'is_reconciled' => $validated['is_reconciled'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new ApTransactionCollection($items))->response();
    }

    public function store(StoreApTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', ApTransaction::class);

        $ap = $this->createService->execute($request->validated());

        return (new ApTransactionResource($ap))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $apTransaction): ApTransactionResource
    {
        $found = $this->findOrFail($apTransaction);
        $this->authorize('view', $found);

        return new ApTransactionResource($found);
    }

    public function update(UpdateApTransactionRequest $request, int $apTransaction): ApTransactionResource
    {
        $found = $this->findOrFail($apTransaction);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $apTransaction;

        return new ApTransactionResource($this->updateService->execute($payload));
    }

    public function destroy(int $apTransaction): JsonResponse
    {
        $found = $this->findOrFail($apTransaction);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $apTransaction]);

        return Response::json(['message' => 'AP transaction deleted successfully']);
    }

    public function reconcile(ReconcileApTransactionRequest $request, int $apTransaction): ApTransactionResource
    {
        $found = $this->findOrFail($apTransaction);
        $this->authorize('update', $found);

        return new ApTransactionResource($this->reconcileService->execute(['id' => $apTransaction]));
    }

    private function findOrFail(int $id): ApTransaction
    {
        $ap = $this->findService->find($id);

        if (! $ap) {
            throw new NotFoundHttpException('AP transaction not found.');
        }

        return $ap;
    }
}
