<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\Services\VoucherWorkflowServiceInterface;
use Modules\Voucher\Presentation\Http\Requests\VoucherWorkflowActionRequest;

final class VoucherWorkflowController extends Controller
{
    public function __construct(private readonly VoucherWorkflowServiceInterface $service)
    {
    }

    public function submit(VoucherWorkflowActionRequest $request, int $voucher): JsonResponse
    {
        return $this->respond($this->service->submit($voucher, $request->validated()));
    }

    public function approve(VoucherWorkflowActionRequest $request, int $voucher): JsonResponse
    {
        return $this->respond($this->service->approve($voucher, $request->validated()));
    }

    public function reject(VoucherWorkflowActionRequest $request, int $voucher): JsonResponse
    {
        return $this->respond($this->service->reject($voucher, $request->validated()));
    }

    public function post(VoucherWorkflowActionRequest $request, int $voucher): JsonResponse
    {
        return $this->respond($this->service->post($voucher, $request->validated()));
    }

    public function cancel(VoucherWorkflowActionRequest $request, int $voucher): JsonResponse
    {
        return $this->respond($this->service->cancel($voucher, $request->validated()));
    }

    public function reverse(VoucherWorkflowActionRequest $request, int $voucher): JsonResponse
    {
        return $this->respond($this->service->reverse($voucher, $request->validated()));
    }

    public function history(int $voucher): JsonResponse
    {
        return $this->respond($this->service->history($voucher));
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
            return response()->json(['data' => $value->toArray()]);
        }

        return response()->json(['data' => $value]);
    }
}
