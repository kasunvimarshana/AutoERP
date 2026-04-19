<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Contracts\FindTimezonesServiceInterface;
use Modules\Shared\Infrastructure\Http\Resources\TimezoneResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TimezoneController extends Controller
{
    public function __construct(
        private readonly FindTimezonesServiceInterface $findTimezonesService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['name']);
        $perPage = (int) $request->query('per_page', '15');
        $page = (int) $request->query('page', '1');
        $sort = $request->query('sort');

        $timezones = $this->findTimezonesService->list($filters, $perPage, $page, $sort);

        return TimezoneResource::collection($timezones)->response();
    }

    public function show(int $id): JsonResponse
    {
        $timezone = $this->findTimezonesService->find($id);

        if ($timezone === null) {
            throw new NotFoundHttpException('Timezone not found.');
        }

        return (new TimezoneResource($timezone))->response();
    }
}
