<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\Contracts\FindCountriesServiceInterface;
use Modules\Configuration\Infrastructure\Http\Resources\CountryResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CountryController extends Controller
{
    public function __construct(
        private readonly FindCountriesServiceInterface $findCountriesService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['code', 'name']);
        $perPage = (int) $request->query('per_page', '15');
        $page = (int) $request->query('page', '1');
        $sort = $request->query('sort');

        $countries = $this->findCountriesService->list($filters, $perPage, $page, $sort);

        return CountryResource::collection($countries)->response();
    }

    public function show(int $id): JsonResponse
    {
        $country = $this->findCountriesService->find($id);

        if ($country === null) {
            throw new NotFoundHttpException('Country not found.');
        }

        return (new CountryResource($country))->response();
    }
}
