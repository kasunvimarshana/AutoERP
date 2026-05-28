<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\Services\VoucherUtilityServiceInterface;
use Modules\Voucher\Presentation\Http\Requests\VoucherPreviewNumberRequest;

final class VoucherUtilityController extends Controller
{
    public function __construct(private readonly VoucherUtilityServiceInterface $service)
    {
    }

    public function previewNumber(VoucherPreviewNumberRequest $request): JsonResponse
    {
        $payload = $request->validated();

        return $this->respond($this->service->previewNumber(
            (int) $payload['tenant_id'],
            isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            (string) $payload['type_code'],
        ));
    }

    public function validateBalance(Request $request): JsonResponse
    {
        $lines = is_array($request->input('lines')) ? $request->input('lines') : [];

        return $this->respond($this->service->validateBalance($lines));
    }

    public function validatePaymentMethod(Request $request): JsonResponse
    {
        return $this->respond($this->service->validatePaymentMethod(
            (int) $request->input('tenant_id', 0),
            (int) $request->input('payment_method_id', 0),
        ));
    }

    public function previewPosting(int $voucher): JsonResponse
    {
        return $this->respond($this->service->previewPosting($voucher));
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

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
