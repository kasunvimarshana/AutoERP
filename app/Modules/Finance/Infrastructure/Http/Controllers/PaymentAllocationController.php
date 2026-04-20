<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreatePaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\FindPaymentAllocationServiceInterface;
use Modules\Finance\Domain\Entities\PaymentAllocation;
use Modules\Finance\Infrastructure\Http\Requests\ListPaymentAllocationRequest;
use Modules\Finance\Infrastructure\Http\Requests\StorePaymentAllocationRequest;
use Modules\Finance\Infrastructure\Http\Resources\PaymentAllocationCollection;
use Modules\Finance\Infrastructure\Http\Resources\PaymentAllocationResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentAllocationController extends AuthorizedController
{
    public function __construct(
        private readonly CreatePaymentAllocationServiceInterface $createService,
        private readonly DeletePaymentAllocationServiceInterface $deleteService,
        private readonly FindPaymentAllocationServiceInterface $findService,
    ) {}

    public function index(ListPaymentAllocationRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentAllocation::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'payment_id' => $validated['payment_id'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new PaymentAllocationCollection($items))->response();
    }

    public function store(StorePaymentAllocationRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentAllocation::class);

        $pa = $this->createService->execute($request->validated());

        return (new PaymentAllocationResource($pa))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $paymentAllocation): PaymentAllocationResource
    {
        $found = $this->findOrFail($paymentAllocation);
        $this->authorize('view', $found);

        return new PaymentAllocationResource($found);
    }

    public function destroy(int $paymentAllocation): JsonResponse
    {
        $found = $this->findOrFail($paymentAllocation);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $paymentAllocation]);

        return Response::json(['message' => 'Payment allocation deleted successfully']);
    }

    private function findOrFail(int $id): PaymentAllocation
    {
        $pa = $this->findService->find($id);

        if (! $pa) {
            throw new NotFoundHttpException('Payment allocation not found.');
        }

        return $pa;
    }
}
