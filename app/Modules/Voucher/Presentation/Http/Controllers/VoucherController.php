<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\Services\VoucherManagementServiceInterface;
use Modules\Voucher\Presentation\Http\Requests\UpsertVoucherAllocationRequest;
use Modules\Voucher\Presentation\Http\Requests\UpsertVoucherLinesRequest;
use Modules\Voucher\Presentation\Http\Requests\UpsertVoucherRequest;
use Modules\Voucher\Presentation\Http\Resources\VoucherResource;

final class VoucherController extends Controller
{
    public function __construct(private readonly VoucherManagementServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->respond($this->service->list($request->all()));
    }

    public function store(UpsertVoucherRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()));
    }

    public function show(int $voucher): JsonResponse
    {
        return $this->respond($this->service->getById($voucher));
    }

    public function update(UpsertVoucherRequest $request, int $voucher): JsonResponse
    {
        return $this->respond($this->service->update($voucher, $request->validated()));
    }

    public function destroy(int $voucher): JsonResponse
    {
        return $this->respond($this->service->delete($voucher));
    }

    public function upsertLines(UpsertVoucherLinesRequest $request, int $voucher): JsonResponse
    {
        return $this->respond($this->service->upsertLines($voucher, $request->validated('lines', [])));
    }

    public function allocations(int $voucher): JsonResponse
    {
        return $this->respond($this->service->listAllocations($voucher));
    }

    public function addAllocation(UpsertVoucherAllocationRequest $request, int $voucher): JsonResponse
    {
        return $this->respond($this->service->addAllocation($voucher, $request->validated()));
    }

    public function updateAllocation(UpsertVoucherAllocationRequest $request, int $allocation): JsonResponse
    {
        return $this->respond($this->service->updateAllocation($allocation, $request->validated()));
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'VOUCHER_NOT_FOUND' ? 404 : 422;

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'meta' => $error->context,
            ], $statusCode);
        }

        $value = $result->valueOrFail();
        if ($value instanceof DataRecord) {
            return response()->json(['data' => new VoucherResource($value->toArray())]);
        }

        if (is_array($value) && isset($value['id'])) {
            return response()->json(['data' => new VoucherResource($value)]);
        }

        if (is_array($value)) {
            $allRecords = true;
            foreach ($value as $item) {
                if (! $item instanceof DataRecord) {
                    $allRecords = false;
                    break;
                }
            }

            if ($allRecords) {
                $rows = array_map(
                    static function (DataRecord $record): array {
                        return (new VoucherResource($record->toArray()))->toArray(request());
                    },
                    $value,
                );

                return response()->json(['data' => $rows]);
            }
        }

        return response()->json(['data' => $value]);
    }
}
