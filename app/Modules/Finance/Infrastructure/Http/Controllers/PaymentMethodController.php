<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreatePaymentMethodServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentMethodServiceInterface;
use Modules\Finance\Application\Contracts\FindPaymentMethodServiceInterface;
use Modules\Finance\Application\Contracts\UpdatePaymentMethodServiceInterface;
use Modules\Finance\Domain\Entities\PaymentMethod;
use Modules\Finance\Infrastructure\Http\Requests\ListPaymentMethodRequest;
use Modules\Finance\Infrastructure\Http\Requests\StorePaymentMethodRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdatePaymentMethodRequest;
use Modules\Finance\Infrastructure\Http\Resources\PaymentMethodCollection;
use Modules\Finance\Infrastructure\Http\Resources\PaymentMethodResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentMethodController extends AuthorizedController
{
    public function __construct(
        private readonly CreatePaymentMethodServiceInterface $createPaymentMethodService,
        private readonly UpdatePaymentMethodServiceInterface $updatePaymentMethodService,
        private readonly DeletePaymentMethodServiceInterface $deletePaymentMethodService,
        private readonly FindPaymentMethodServiceInterface $findPaymentMethodService,
    ) {}

    public function index(ListPaymentMethodRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentMethod::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'type' => $validated['type'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $paymentMethods = $this->findPaymentMethodService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new PaymentMethodCollection($paymentMethods))->response();
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentMethod::class);

        $paymentMethod = $this->createPaymentMethodService->execute($request->validated());

        return (new PaymentMethodResource($paymentMethod))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $paymentMethod): PaymentMethodResource
    {
        $found = $this->findPaymentMethodOrFail($paymentMethod);
        $this->authorize('view', $found);

        return new PaymentMethodResource($found);
    }

    public function update(UpdatePaymentMethodRequest $request, int $paymentMethod): PaymentMethodResource
    {
        $found = $this->findPaymentMethodOrFail($paymentMethod);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $paymentMethod;

        return new PaymentMethodResource($this->updatePaymentMethodService->execute($payload));
    }

    public function destroy(int $paymentMethod): JsonResponse
    {
        $found = $this->findPaymentMethodOrFail($paymentMethod);
        $this->authorize('delete', $found);

        $this->deletePaymentMethodService->execute(['id' => $paymentMethod]);

        return Response::json(['message' => 'Payment method deleted successfully']);
    }

    private function findPaymentMethodOrFail(int $id): PaymentMethod
    {
        $paymentMethod = $this->findPaymentMethodService->find($id);

        if (! $paymentMethod) {
            throw new NotFoundHttpException('Payment method not found.');
        }

        return $paymentMethod;
    }
}
