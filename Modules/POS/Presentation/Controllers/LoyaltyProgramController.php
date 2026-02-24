<?php

namespace Modules\POS\Presentation\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Application\UseCases\CreateLoyaltyProgramUseCase;
use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;

class LoyaltyProgramController extends Controller
{
    public function __construct(
        private LoyaltyProgramRepositoryInterface $loyaltyRepo,
        private CreateLoyaltyProgramUseCase       $createProgram,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');
        $paginator = $this->loyaltyRepo->paginate($tenantId, (int) $request->query('per_page', 20));

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

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', auth()->user()?->tenant_id ?? 'default');

        $request->validate([
            'name'                     => ['required', 'string', 'max:150'],
            'points_per_currency_unit' => ['nullable', 'numeric', 'min:0.00000001'],
            'redemption_rate'          => ['nullable', 'numeric', 'min:0.00000001'],
            'is_active'                => ['nullable', 'boolean'],
            'description'              => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $program = $this->createProgram->execute(
                array_merge($request->all(), ['tenant_id' => $tenantId])
            );
            return response()->json(['data' => $program], 201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $program = $this->loyaltyRepo->findById($id);
        if (! $program) {
            return response()->json(['message' => 'Loyalty program not found.'], 404);
        }
        return response()->json(['data' => $program]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $program = $this->loyaltyRepo->findById($id);
        if (! $program) {
            return response()->json(['message' => 'Loyalty program not found.'], 404);
        }

        $request->validate([
            'name'                     => ['sometimes', 'required', 'string', 'max:150'],
            'points_per_currency_unit' => ['nullable', 'numeric', 'min:0.00000001'],
            'redemption_rate'          => ['nullable', 'numeric', 'min:0.00000001'],
            'is_active'                => ['nullable', 'boolean'],
            'description'              => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $updated = $this->loyaltyRepo->update($id, $request->all());
            return response()->json(['data' => $updated]);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $program = $this->loyaltyRepo->findById($id);
        if (! $program) {
            return response()->json(['message' => 'Loyalty program not found.'], 404);
        }

        $this->loyaltyRepo->delete($id);
        return response()->json(null, 204);
    }
}
