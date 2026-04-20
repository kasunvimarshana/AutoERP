<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\ApplyCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\CreateCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\DeleteCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\FindCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\IssueCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\UpdateCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Finance\Domain\Entities\CreditMemo;
use Modules\Finance\Infrastructure\Http\Requests\ApplyCreditMemoRequest;
use Modules\Finance\Infrastructure\Http\Requests\IssueCreditMemoRequest;
use Modules\Finance\Infrastructure\Http\Requests\ListCreditMemoRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreCreditMemoRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateCreditMemoRequest;
use Modules\Finance\Infrastructure\Http\Requests\VoidCreditMemoRequest;
use Modules\Finance\Infrastructure\Http\Resources\CreditMemoCollection;
use Modules\Finance\Infrastructure\Http\Resources\CreditMemoResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreditMemoController extends AuthorizedController
{
    public function __construct(
        private readonly CreateCreditMemoServiceInterface $createService,
        private readonly UpdateCreditMemoServiceInterface $updateService,
        private readonly DeleteCreditMemoServiceInterface $deleteService,
        private readonly FindCreditMemoServiceInterface $findService,
        private readonly IssueCreditMemoServiceInterface $issueService,
        private readonly ApplyCreditMemoServiceInterface $applyService,
        private readonly VoidCreditMemoServiceInterface $voidService,
    ) {}

    public function index(ListCreditMemoRequest $request): JsonResponse
    {
        $this->authorize('viewAny', CreditMemo::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'party_id' => $validated['party_id'] ?? null,
            'party_type' => $validated['party_type'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new CreditMemoCollection($items))->response();
    }

    public function store(StoreCreditMemoRequest $request): JsonResponse
    {
        $this->authorize('create', CreditMemo::class);

        $cm = $this->createService->execute($request->validated());

        return (new CreditMemoResource($cm))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $creditMemo): CreditMemoResource
    {
        $found = $this->findOrFail($creditMemo);
        $this->authorize('view', $found);

        return new CreditMemoResource($found);
    }

    public function update(UpdateCreditMemoRequest $request, int $creditMemo): CreditMemoResource
    {
        $found = $this->findOrFail($creditMemo);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $creditMemo;

        return new CreditMemoResource($this->updateService->execute($payload));
    }

    public function destroy(int $creditMemo): JsonResponse
    {
        $found = $this->findOrFail($creditMemo);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $creditMemo]);

        return Response::json(['message' => 'Credit memo deleted successfully']);
    }

    public function issue(IssueCreditMemoRequest $request, int $creditMemo): CreditMemoResource
    {
        $found = $this->findOrFail($creditMemo);
        $this->authorize('update', $found);

        return new CreditMemoResource($this->issueService->execute(['id' => $creditMemo]));
    }

    public function apply(ApplyCreditMemoRequest $request, int $creditMemo): CreditMemoResource
    {
        $found = $this->findOrFail($creditMemo);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $creditMemo;

        return new CreditMemoResource($this->applyService->execute($payload));
    }

    public function voidMemo(VoidCreditMemoRequest $request, int $creditMemo): CreditMemoResource
    {
        $found = $this->findOrFail($creditMemo);
        $this->authorize('update', $found);

        return new CreditMemoResource($this->voidService->execute(['id' => $creditMemo]));
    }

    private function findOrFail(int $id): CreditMemo
    {
        $cm = $this->findService->find($id);

        if (! $cm) {
            throw new NotFoundHttpException('Credit memo not found.');
        }

        return $cm;
    }
}
