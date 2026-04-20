<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreatePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\FindPaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\UpdatePaymentTermServiceInterface;
use Modules\Finance\Domain\Entities\PaymentTerm;
use Modules\Finance\Infrastructure\Http\Requests\ListPaymentTermRequest;
use Modules\Finance\Infrastructure\Http\Requests\StorePaymentTermRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdatePaymentTermRequest;
use Modules\Finance\Infrastructure\Http\Resources\PaymentTermCollection;
use Modules\Finance\Infrastructure\Http\Resources\PaymentTermResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentTermController extends AuthorizedController
{
    public function __construct(
        private readonly CreatePaymentTermServiceInterface $createPaymentTermService,
        private readonly UpdatePaymentTermServiceInterface $updatePaymentTermService,
        private readonly DeletePaymentTermServiceInterface $deletePaymentTermService,
        private readonly FindPaymentTermServiceInterface $findPaymentTermService,
    ) {}

    public function index(ListPaymentTermRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentTerm::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $paymentTerms = $this->findPaymentTermService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new PaymentTermCollection($paymentTerms))->response();
    }

    public function store(StorePaymentTermRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentTerm::class);

        $paymentTerm = $this->createPaymentTermService->execute($request->validated());

        return (new PaymentTermResource($paymentTerm))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $paymentTerm): PaymentTermResource
    {
        $found = $this->findPaymentTermOrFail($paymentTerm);
        $this->authorize('view', $found);

        return new PaymentTermResource($found);
    }

    public function update(UpdatePaymentTermRequest $request, int $paymentTerm): PaymentTermResource
    {
        $found = $this->findPaymentTermOrFail($paymentTerm);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $paymentTerm;

        return new PaymentTermResource($this->updatePaymentTermService->execute($payload));
    }

    public function destroy(int $paymentTerm): JsonResponse
    {
        $found = $this->findPaymentTermOrFail($paymentTerm);
        $this->authorize('delete', $found);

        $this->deletePaymentTermService->execute(['id' => $paymentTerm]);

        return Response::json(['message' => 'Payment term deleted successfully']);
    }

    private function findPaymentTermOrFail(int $id): PaymentTerm
    {
        $paymentTerm = $this->findPaymentTermService->find($id);

        if (! $paymentTerm) {
            throw new NotFoundHttpException('Payment term not found.');
        }

        return $paymentTerm;
    }
}
