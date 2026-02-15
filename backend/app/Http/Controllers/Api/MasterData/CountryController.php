<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CountryResource;
use App\Modules\MasterData\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CountryController extends BaseController
{
    /**
     * Display a listing of countries.
     *
     * @OA\Get(
     *     path="/api/v1/master-data/countries",
     *     tags={"Master Data - Countries"},
     *     summary="Get list of countries",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $countries = Country::orderBy('name')->get();
        return CountryResource::collection($countries);
    }

    /**
     * Display the specified country.
     *
     * @OA\Get(
     *     path="/api/v1/master-data/countries/{code}",
     *     tags={"Master Data - Countries"},
     *     summary="Get country details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         description="Country code (ISO 3166-1 alpha-2)",
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
        $country = Country::where('code', $code)->first();

        if (!$country) {
            return $this->errorResponse('Country not found', [], 404);
        }

        return $this->successResponse(new CountryResource($country));
    }
}
