<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Taxation\StoreTaxJurisdictionRequest;
use App\Http\Requests\Taxation\UpdateTaxJurisdictionRequest;
use App\Http\Resources\Taxation\TaxJurisdictionResource;
use App\Modules\Taxation\Services\TaxJurisdictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaxJurisdictionController extends BaseController
{
    public function __construct(
        private readonly TaxJurisdictionService $taxJurisdictionService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $taxJurisdictions = $this->taxJurisdictionService->getAll([], $perPage);

        return TaxJurisdictionResource::collection($taxJurisdictions);
    }

    public function store(StoreTaxJurisdictionRequest $request): JsonResponse
    {
        try {
            $taxJurisdiction = $this->taxJurisdictionService->create($request->validated());

            return $this->successResponse(
                new TaxJurisdictionResource($taxJurisdiction),
                'Tax jurisdiction created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create tax jurisdiction',
                [$e->getMessage()],
                500
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $taxJurisdiction = $this->taxJurisdictionService->getById($id);

            return $this->successResponse(new TaxJurisdictionResource($taxJurisdiction));
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Tax jurisdiction not found',
                [$e->getMessage()],
                404
            );
        }
    }

    public function update(UpdateTaxJurisdictionRequest $request, int $id): JsonResponse
    {
        try {
            $taxJurisdiction = $this->taxJurisdictionService->update($id, $request->validated());

            return $this->successResponse(
                new TaxJurisdictionResource($taxJurisdiction),
                'Tax jurisdiction updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update tax jurisdiction',
                [$e->getMessage()],
                500
            );
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->taxJurisdictionService->delete($id);

            return $this->successResponse(
                null,
                'Tax jurisdiction deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete tax jurisdiction',
                [$e->getMessage()],
                500
            );
        }
    }

    public function active(): AnonymousResourceCollection
    {
        $taxJurisdictions = $this->taxJurisdictionService->getActiveJurisdictions();
        return TaxJurisdictionResource::collection($taxJurisdictions);
    }

    public function findByLocation(Request $request): JsonResponse
    {
        $request->validate([
            'country_code' => ['nullable', 'string', 'size:2'],
            'state_code' => ['nullable', 'string', 'max:10'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $taxJurisdiction = $this->taxJurisdictionService->findByLocation(
            $request->input('country_code'),
            $request->input('state_code'),
            $request->input('city_name'),
            $request->input('postal_code')
        );

        if (!$taxJurisdiction) {
            return $this->errorResponse('No matching jurisdiction found', [], 404);
        }

        return $this->successResponse(new TaxJurisdictionResource($taxJurisdiction));
    }
}
