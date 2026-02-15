<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CurrencyResource;
use App\Modules\MasterData\Repositories\CurrencyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CurrencyController extends BaseController
{
    public function __construct(
        private readonly CurrencyRepository $currencyRepository
    ) {}

    /**
     * Display a listing of currencies.
     *
     * @OA\Get(
     *     path="/api/v1/master-data/currencies",
     *     tags={"Master Data - Currencies"},
     *     summary="Get list of currencies",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $currencies = $this->currencyRepository->all();
        return CurrencyResource::collection($currencies);
    }

    /**
     * Store a newly created currency.
     *
     * @OA\Post(
     *     path="/api/v1/master-data/currencies",
     *     tags={"Master Data - Currencies"},
     *     summary="Create a new currency",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "name", "symbol"},
     *             @OA\Property(property="code", type="string", example="USD"),
     *             @OA\Property(property="name", type="string", example="US Dollar"),
     *             @OA\Property(property="symbol", type="string", example="$"),
     *             @OA\Property(property="exchange_rate", type="number", example=1.0),
     *             @OA\Property(property="is_base", type="boolean", example=false),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Currency created successfully"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:3', 'unique:currencies,code'],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'is_base' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $currency = $this->currencyRepository->create($validated);

            return $this->successResponse(
                new CurrencyResource($currency),
                'Currency created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create currency',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified currency.
     *
     * @OA\Get(
     *     path="/api/v1/master-data/currencies/{code}",
     *     tags={"Master Data - Currencies"},
     *     summary="Get currency details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         description="Currency code",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function show(string $code): JsonResponse
    {
        $currency = $this->currencyRepository->findByCode($code);

        if (!$currency) {
            return $this->errorResponse('Currency not found', [], 404);
        }

        return $this->successResponse(new CurrencyResource($currency));
    }

    /**
     * Update the specified currency.
     *
     * @OA\Put(
     *     path="/api/v1/master-data/currencies/{code}",
     *     tags={"Master Data - Currencies"},
     *     summary="Update currency",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         description="Currency code",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="symbol", type="string"),
     *             @OA\Property(property="exchange_rate", type="number"),
     *             @OA\Property(property="is_base", type="boolean"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Currency updated successfully"
     *     )
     * )
     */
    public function update(Request $request, string $code): JsonResponse
    {
        $currency = $this->currencyRepository->findByCode($code);

        if (!$currency) {
            return $this->errorResponse('Currency not found', [], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'symbol' => ['sometimes', 'required', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'is_base' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $currency = $this->currencyRepository->update($currency, $validated);

            return $this->successResponse(
                new CurrencyResource($currency),
                'Currency updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update currency',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Remove the specified currency.
     *
     * @OA\Delete(
     *     path="/api/v1/master-data/currencies/{code}",
     *     tags={"Master Data - Currencies"},
     *     summary="Delete currency",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         description="Currency code",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Currency deleted successfully"
     *     )
     * )
     */
    public function destroy(string $code): JsonResponse
    {
        $currency = $this->currencyRepository->findByCode($code);

        if (!$currency) {
            return $this->errorResponse('Currency not found', [], 404);
        }

        try {
            $this->currencyRepository->delete($currency);

            return $this->successResponse(
                null,
                'Currency deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete currency',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Get active currencies.
     *
     * @OA\Get(
     *     path="/api/v1/master-data/currencies/active",
     *     tags={"Master Data - Currencies"},
     *     summary="Get active currencies",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function active(): AnonymousResourceCollection
    {
        $currencies = $this->currencyRepository->getActiveCurrencies();
        return CurrencyResource::collection($currencies);
    }

    /**
     * Get base currency.
     *
     * @OA\Get(
     *     path="/api/v1/master-data/currencies/base",
     *     tags={"Master Data - Currencies"},
     *     summary="Get base currency",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function base(): JsonResponse
    {
        $currency = $this->currencyRepository->getBaseCurrency();

        if (!$currency) {
            return $this->errorResponse('Base currency not found', [], 404);
        }

        return $this->successResponse(new CurrencyResource($currency));
    }
}
