<?php

namespace Modules\POS\Presentation\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Application\UseCases\CreatePosDiscountUseCase;
use Modules\POS\Domain\Contracts\PosDiscountRepositoryInterface;
use Modules\POS\Presentation\Requests\StorePosDiscountRequest;
use Modules\POS\Presentation\Requests\ValidateDiscountRequest;

class PosDiscountController extends Controller
{
    public function __construct(
        private PosDiscountRepositoryInterface $discountRepo,
        private CreatePosDiscountUseCase       $createDiscount,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');
        $filters   = $request->only(['is_active', 'type', 'search']);
        $paginator = $this->discountRepo->paginate($tenantId, $filters, (int) $request->query('per_page', 20));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
            ],
        ]);
    }

    public function store(StorePosDiscountRequest $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');

        try {
            $discount = $this->createDiscount->execute(
                array_merge($request->validated(), ['tenant_id' => $tenantId])
            );
            return response()->json(['data' => $discount], 201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $discount = $this->discountRepo->findById($id);
        if (! $discount) {
            return response()->json(['message' => 'Discount not found.'], 404);
        }
        return response()->json(['data' => $discount]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $discount = $this->discountRepo->findById($id);
        if (! $discount) {
            return response()->json(['message' => 'Discount not found.'], 404);
        }

        $request->validate([
            'code'        => ['sometimes', 'required', 'string', 'max:50'],
            'name'        => ['sometimes', 'required', 'string', 'max:150'],
            'type'        => ['sometimes', 'required', 'in:percentage,fixed_amount'],
            'value'       => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at'  => ['nullable', 'date'],
            'is_active'   => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = $request->all();
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        try {
            $updated = $this->discountRepo->update($id, $data);
            return response()->json(['data' => $updated]);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $discount = $this->discountRepo->findById($id);
        if (! $discount) {
            return response()->json(['message' => 'Discount not found.'], 404);
        }

        $this->discountRepo->delete($id);
        return response()->json(null, 204);
    }

    /**
     * Validate a discount code and return the computed discount amount for a given subtotal.
     *
     * POST /api/v1/pos/discounts/{code}/validate
     * Body: { "subtotal": "100.00" }
     */
    public function validateCode(ValidateDiscountRequest $request, string $code): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');
        $scale    = 8;

        $discount = $this->discountRepo->findByCode($tenantId, $code);

        if (! $discount) {
            return response()->json(['message' => 'Discount code not found.'], 404);
        }
        if (! $discount->is_active) {
            return response()->json(['message' => 'Discount code is inactive.'], 422);
        }
        if ($discount->expires_at !== null &&
            strtotime((string) $discount->expires_at) < time()) {
            return response()->json(['message' => 'Discount code has expired.'], 422);
        }
        if ($discount->usage_limit !== null &&
            $discount->times_used >= $discount->usage_limit) {
            return response()->json(['message' => 'Discount code usage limit has been reached.'], 422);
        }

        $subtotal      = (string) $request->input('subtotal', '0');
        $discountValue = (string) $discount->value;

        if ($discount->type === 'percentage') {
            $computed       = bcmul($subtotal, bcdiv($discountValue, '100', $scale), $scale);
            $discountAmount = bccomp($computed, $subtotal, $scale) > 0 ? $subtotal : $computed;
        } else {
            $discountAmount = bccomp($discountValue, $subtotal, $scale) > 0 ? $subtotal : $discountValue;
        }

        return response()->json([
            'data' => [
                'code'            => $discount->code,
                'name'            => $discount->name,
                'type'            => $discount->type,
                'value'           => $discountValue,
                'discount_amount' => $discountAmount,
            ],
        ]);
    }
}
