<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentServiceInterface;
use Modules\Finance\Application\Contracts\FindPaymentServiceInterface;
use Modules\Finance\Application\Contracts\PostPaymentServiceInterface;
use Modules\Finance\Application\Contracts\UpdatePaymentServiceInterface;
use Modules\Finance\Application\Contracts\VoidPaymentServiceInterface;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Infrastructure\Http\Requests\ListPaymentRequest;
use Modules\Finance\Infrastructure\Http\Requests\PostPaymentRequest;
use Modules\Finance\Infrastructure\Http\Requests\StorePaymentRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdatePaymentRequest;
use Modules\Finance\Infrastructure\Http\Requests\VoidPaymentRequest;
use Modules\Finance\Infrastructure\Http\Resources\PaymentCollection;
use Modules\Finance\Infrastructure\Http\Resources\PaymentResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentController extends AuthorizedController
{
    public function __construct(
        private readonly CreatePaymentServiceInterface $createPaymentService,
        private readonly UpdatePaymentServiceInterface $updatePaymentService,
        private readonly DeletePaymentServiceInterface $deletePaymentService,
        private readonly FindPaymentServiceInterface $findPaymentService,
        private readonly PostPaymentServiceInterface $postPaymentService,
        private readonly VoidPaymentServiceInterface $voidPaymentService,
    ) {}

    public function index(ListPaymentRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'direction' => $validated['direction'] ?? null,
            'party_type' => $validated['party_type'] ?? null,
            'party_id' => $validated['party_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $payments = $this->findPaymentService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new PaymentCollection($payments))->response();
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $payment = $this->createPaymentService->execute($request->validated());

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $payment): PaymentResource
    {
        $found = $this->findPaymentOrFail($payment);
        $this->authorize('view', $found);

        return new PaymentResource($found);
    }

    public function update(UpdatePaymentRequest $request, int $payment): PaymentResource
    {
        $found = $this->findPaymentOrFail($payment);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $payment;

        return new PaymentResource($this->updatePaymentService->execute($payload));
    }

    public function destroy(int $payment): JsonResponse
    {
        $found = $this->findPaymentOrFail($payment);
        $this->authorize('delete', $found);

        $this->deletePaymentService->execute(['id' => $payment]);

        return Response::json(['message' => 'Payment deleted successfully']);
    }

    public function post(PostPaymentRequest $request, int $payment): PaymentResource
    {
        $found = $this->findPaymentOrFail($payment);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $payment;

        return new PaymentResource($this->postPaymentService->execute($payload));
    }

    public function void(VoidPaymentRequest $request, int $payment): PaymentResource
    {
        $found = $this->findPaymentOrFail($payment);
        $this->authorize('update', $found);

        return new PaymentResource($this->voidPaymentService->execute(['id' => $payment]));
    }

    private function findPaymentOrFail(int $id): Payment
    {
        $payment = $this->findPaymentService->find($id);

        if (! $payment) {
            throw new NotFoundHttpException('Payment not found.');
        }

        return $payment;
    }
}
