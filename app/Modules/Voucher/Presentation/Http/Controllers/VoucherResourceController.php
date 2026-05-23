<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Voucher\Application\DTOs\VoucherRecordData;
use Modules\Voucher\Application\Services\VoucherService;
use Modules\Voucher\Domain\Exceptions\VoucherIntegrityException;
use Modules\Voucher\Domain\Exceptions\VoucherRecordNotFoundException;
use Modules\Voucher\Presentation\Http\Requests\VoucherRecordRequest;
use Modules\Voucher\Presentation\Http\Resources\VoucherRecordResource;

class VoucherResourceController extends Controller
{
    public function __construct(private readonly VoucherService $vouchers) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return VoucherRecordResource::collection($this->vouchers->list($resource, $tenant, $this->filters($request), $this->perPage($request)));
        } catch (VoucherRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(VoucherRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->vouchers->create($resource, VoucherRecordData::fromArray($tenant, $request->validated()));

            return (new VoucherRecordResource($record))->response()->setStatusCode(201);
        } catch (VoucherIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (VoucherRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): VoucherRecordResource|JsonResponse
    {
        try {
            return new VoucherRecordResource($this->vouchers->find($resource, $tenant, $id));
        } catch (VoucherRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(VoucherRecordRequest $request, int|string $tenant, string $resource, int|string $id): VoucherRecordResource|JsonResponse
    {
        try {
            return new VoucherRecordResource($this->vouchers->update($resource, $tenant, $id, VoucherRecordData::fromArray($tenant, $request->validated())));
        } catch (VoucherIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (VoucherRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->vouchers->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (VoucherIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (VoucherRecordNotFoundException $exception) {
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
            'type',
            'sub_type',
            'status',
            'party_type',
            'party_id',
            'account_id',
            'contra_account_id',
            'tax_rate_id',
            'frequency',
            'is_active',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(VoucherRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(VoucherIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
