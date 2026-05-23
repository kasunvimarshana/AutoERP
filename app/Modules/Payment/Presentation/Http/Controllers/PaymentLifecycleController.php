<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Payment\Application\Services\PaymentService;
use Modules\Payment\Domain\Exceptions\PaymentIntegrityException;
use Modules\Payment\Domain\Exceptions\PaymentRecordNotFoundException;
use Modules\Payment\Presentation\Http\Resources\PaymentRecordResource;

class PaymentLifecycleController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function post(int|string $tenant, int|string $payment): PaymentRecordResource|JsonResponse
    {
        try {
            return new PaymentRecordResource($this->payments->postPayment($tenant, $payment));
        } catch (PaymentIntegrityException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (PaymentRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function recalculateAdvance(int|string $tenant, int|string $advancePayment): PaymentRecordResource|JsonResponse
    {
        try {
            return new PaymentRecordResource($this->payments->recalculateAdvancePayment($tenant, $advancePayment));
        } catch (PaymentIntegrityException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (PaymentRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
