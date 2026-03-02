<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Domain\Contracts\TaxRateRepositoryInterface;
use Modules\Accounting\Domain\Entities\TaxRate;

/**
 * CRUD controller for Tax Rates.
 * All rate values are stored as BCMath-safe decimal strings.
 */
class TaxRateController extends Controller
{
    public function __construct(
        private readonly TaxRateRepositoryInterface $taxRates,
    ) {}

    /**
     * @OA\Get(path="/api/v1/accounting/tax-rates", summary="List tax rates for the tenant")
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $items    = $this->taxRates->findAll($tenantId);

        return response()->json([
            'success' => true,
            'message' => 'Tax rates retrieved.',
            'data'    => array_map(fn (TaxRate $t) => $this->format($t), $items),
            'errors'  => null,
        ]);
    }

    /**
     * @OA\Post(path="/api/v1/accounting/tax-rates", summary="Create a tax rate")
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'rate'        => 'required|numeric|min:0|max:100',
            'type'        => 'required|in:percentage,fixed',
            'is_active'   => 'nullable|boolean',
            'is_compound' => 'nullable|boolean',
        ]);

        $taxRate = new TaxRate(
            id: 0,
            tenantId: (int) $request->attributes->get('tenant_id'),
            name: $validated['name'],
            rate: bcadd((string) $validated['rate'], '0', 4),
            type: $validated['type'],
            isActive: $validated['is_active'] ?? true,
            isCompound: $validated['is_compound'] ?? false,
        );

        $saved = $this->taxRates->save($taxRate);

        return response()->json([
            'success' => true,
            'message' => 'Tax rate created.',
            'data'    => $this->format($saved),
            'errors'  => null,
        ], 201);
    }

    /**
     * @OA\Get(path="/api/v1/accounting/tax-rates/{id}", summary="Show a tax rate")
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $taxRate  = $this->taxRates->findById($id, $tenantId);

        if ($taxRate === null) {
            return response()->json(['success' => false, 'message' => 'Tax rate not found.', 'data' => null, 'errors' => null], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tax rate retrieved.',
            'data'    => $this->format($taxRate),
            'errors'  => null,
        ]);
    }

    /**
     * @OA\Put(path="/api/v1/accounting/tax-rates/{id}", summary="Update a tax rate")
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $existing = $this->taxRates->findById($id, $tenantId);

        if ($existing === null) {
            return response()->json(['success' => false, 'message' => 'Tax rate not found.', 'data' => null, 'errors' => null], 404);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:100',
            'rate'        => 'sometimes|required|numeric|min:0|max:100',
            'type'        => 'sometimes|required|in:percentage,fixed',
            'is_active'   => 'nullable|boolean',
            'is_compound' => 'nullable|boolean',
        ]);

        $taxRate = new TaxRate(
            id: $existing->getId(),
            tenantId: $existing->getTenantId(),
            name: $validated['name'] ?? $existing->getName(),
            rate: isset($validated['rate']) ? bcadd((string) $validated['rate'], '0', 4) : $existing->getRate(),
            type: $validated['type'] ?? $existing->getType(),
            isActive: $validated['is_active'] ?? $existing->isActive(),
            isCompound: $validated['is_compound'] ?? $existing->isCompound(),
        );

        $saved = $this->taxRates->save($taxRate);

        return response()->json([
            'success' => true,
            'message' => 'Tax rate updated.',
            'data'    => $this->format($saved),
            'errors'  => null,
        ]);
    }

    /**
     * @OA\Delete(path="/api/v1/accounting/tax-rates/{id}", summary="Delete a tax rate")
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        if ($this->taxRates->findById($id, $tenantId) === null) {
            return response()->json(['success' => false, 'message' => 'Tax rate not found.', 'data' => null, 'errors' => null], 404);
        }

        $this->taxRates->delete($id, $tenantId);

        return response()->json([
            'success' => true,
            'message' => 'Tax rate deleted.',
            'data'    => null,
            'errors'  => null,
        ]);
    }

    private function format(TaxRate $t): array
    {
        return [
            'id'          => $t->getId(),
            'name'        => $t->getName(),
            'rate'        => $t->getRate(),
            'type'        => $t->getType(),
            'is_active'   => $t->isActive(),
            'is_compound' => $t->isCompound(),
        ];
    }
}

