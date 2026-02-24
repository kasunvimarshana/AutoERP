<?php

namespace Modules\POS\Presentation\Controllers;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Application\UseCases\AccrueLoyaltyPointsUseCase;
use Modules\POS\Application\UseCases\RedeemLoyaltyPointsUseCase;
use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;

class LoyaltyCardController extends Controller
{
    public function __construct(
        private LoyaltyProgramRepositoryInterface $loyaltyRepo,
        private AccrueLoyaltyPointsUseCase        $accruePoints,
        private RedeemLoyaltyPointsUseCase        $redeemPoints,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');
        $filters   = array_filter([
            'program_id'  => $request->query('program_id'),
            'customer_id' => $request->query('customer_id'),
        ]);
        $paginator = $this->loyaltyRepo->paginateCards($tenantId, $filters, (int) $request->query('per_page', 20));

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

    public function show(string $id): JsonResponse
    {
        $card = $this->loyaltyRepo->findCardById($id);
        if (! $card) {
            return response()->json(['message' => 'Loyalty card not found.'], 404);
        }
        return response()->json(['data' => $card]);
    }

    public function accrue(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');

        $request->validate([
            'program_id'   => ['required', 'uuid'],
            'customer_id'  => ['required', 'uuid'],
            'order_amount' => ['required', 'numeric', 'min:0.01'],
            'reference'    => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $card = $this->accruePoints->execute(
                array_merge($request->all(), ['tenant_id' => $tenantId])
            );
            return response()->json(['data' => $card]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function redeem(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'points_to_redeem' => ['required', 'integer', 'min:1'],
            'reference'        => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $result = $this->redeemPoints->execute(
                array_merge($request->all(), ['card_id' => $id])
            );
            return response()->json(['data' => $result]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
