<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Voucher\Application\Services\VoucherService;
use Modules\Voucher\Domain\Exceptions\VoucherIntegrityException;
use Modules\Voucher\Domain\Exceptions\VoucherRecordNotFoundException;
use Modules\Voucher\Presentation\Http\Resources\VoucherRecordResource;

class VoucherLifecycleController extends Controller
{
    public function __construct(private readonly VoucherService $vouchers) {}

    public function post(int|string $tenant, int|string $voucher): VoucherRecordResource|JsonResponse
    {
        try {
            return new VoucherRecordResource($this->vouchers->post($tenant, $voucher));
        } catch (VoucherIntegrityException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (VoucherRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function generate(Request $request, int|string $tenant, int|string $recurringVoucher): VoucherRecordResource|JsonResponse
    {
        try {
            return new VoucherRecordResource($this->vouchers->generateFromRecurring(
                $tenant,
                $recurringVoucher,
                $request->filled('voucher_number') ? (string) $request->input('voucher_number') : null
            ));
        } catch (VoucherIntegrityException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (VoucherRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
