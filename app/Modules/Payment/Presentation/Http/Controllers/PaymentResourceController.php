<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payment\Application\DTOs\PaymentRecordData;
use Modules\Payment\Application\Services\PaymentService;
use Modules\Payment\Domain\Exceptions\PaymentIntegrityException;
use Modules\Payment\Domain\Exceptions\PaymentRecordNotFoundException;
use Modules\Payment\Presentation\Http\Requests\PaymentRecordRequest;
use Modules\Payment\Presentation\Http\Resources\PaymentRecordResource;

class PaymentResourceController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return PaymentRecordResource::collection($this->payments->list($resource, $tenant, $this->filters($request), $this->perPage($request)));
        } catch (PaymentRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(PaymentRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->payments->create($resource, PaymentRecordData::fromArray($tenant, $request->validated()));

            return (new PaymentRecordResource($record))->response()->setStatusCode(201);
        } catch (PaymentIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (PaymentRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): PaymentRecordResource|JsonResponse
    {
        try {
            return new PaymentRecordResource($this->payments->find($resource, $tenant, $id));
        } catch (PaymentRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(PaymentRecordRequest $request, int|string $tenant, string $resource, int|string $id): PaymentRecordResource|JsonResponse
    {
        try {
            return new PaymentRecordResource($this->payments->update($resource, $tenant, $id, PaymentRecordData::fromArray($tenant, $request->validated())));
        } catch (PaymentIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (PaymentRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->payments->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (PaymentIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (PaymentRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return collect($request->only([
            'organization_unit_id',
            'party_type',
            'party_id',
            'payment_id',
            'advance_payment_id',
            'payment_method_id',
            'account_id',
            'bank_account_id',
            'cash_account_id',
            'currency_id',
            'journal_entry_id',
            'document_type',
            'document_id',
            'status',
            'type',
            'direction',
            'reference',
            'payment_number',
            'advance_number',
            'check_number',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(PaymentRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(PaymentIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
