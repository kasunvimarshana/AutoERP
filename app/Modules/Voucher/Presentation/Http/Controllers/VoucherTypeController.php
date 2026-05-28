<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\Services\VoucherTypeServiceInterface;
use Modules\Voucher\Presentation\Http\Requests\UpsertVoucherTypeRequest;
use Modules\Voucher\Presentation\Http\Resources\VoucherTypeResource;

final class VoucherTypeController extends Controller
{
    public function __construct(private readonly VoucherTypeServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->respond($this->service->list($request->all()));
    }

    public function store(UpsertVoucherTypeRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()));
    }

    public function show(int $type): JsonResponse
    {
        return $this->respond($this->service->list(['id' => $type]));
    }

    public function update(UpsertVoucherTypeRequest $request, int $type): JsonResponse
    {
        return $this->respond($this->service->update($type, $request->validated()));
    }

    public function activate(int $type): JsonResponse
    {
        return $this->respond($this->service->setActive($type, true));
    }

    public function deactivate(int $type): JsonResponse
    {
        return $this->respond($this->service->setActive($type, false));
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
            return response()->json(['data' => new VoucherTypeResource($value->toArray())]);
        }

        if (is_array($value)) {
            $rows = [];
            foreach ($value as $item) {
                if ($item instanceof DataRecord) {
                    $rows[] = (new VoucherTypeResource($item->toArray()))->toArray(request());
                }
            }

            if ($rows !== []) {
                return response()->json(['data' => $rows]);
            }
        }

        return response()->json(['data' => $value]);
    }
}
