<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\CreateTimezoneServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\DeleteTimezoneServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\GetTimezoneServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\ListTimezonesServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\UpdateTimezoneServiceInterface;
use Modules\Configuration\Presentation\Http\Requests\ListTimezoneRequest;
use Modules\Configuration\Presentation\Http\Requests\UpsertTimezoneRequest;
use Modules\Configuration\Presentation\Http\Resources\TimezoneResource;
use Modules\Core\Application\DTO\PagedResult;

final class TimezoneController extends Controller
{
    public function __construct(
        private readonly ListTimezonesServiceInterface $listTimezones,
        private readonly GetTimezoneServiceInterface $getTimezone,
        private readonly CreateTimezoneServiceInterface $createTimezone,
        private readonly UpdateTimezoneServiceInterface $updateTimezone,
        private readonly DeleteTimezoneServiceInterface $deleteTimezone,
    ) {
    }

    public function index(ListTimezoneRequest $request): JsonResponse
    {
        $criteria = [];
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $criteria['name'] = (string) $validated['name'];
        }
        if (isset($validated['offset'])) {
            $criteria['offset'] = (string) $validated['offset'];
        }

        $result = $this->listTimezones->execute(
            $criteria,
            (int) ($validated['per_page'] ?? 0),
            (int) ($validated['page'] ?? 0),
        );

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => TimezoneResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $timezone): JsonResponse|TimezoneResource
    {
        $result = $this->getTimezone->execute($timezone);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TimezoneResource($result->valueOrFail());
    }

    public function store(UpsertTimezoneRequest $request): JsonResponse|TimezoneResource
    {
        $result = $this->createTimezone->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TimezoneResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertTimezoneRequest $request, int|string $timezone): JsonResponse|TimezoneResource
    {
        $result = $this->updateTimezone->execute($timezone, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CONFIGURATION_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TimezoneResource($result->valueOrFail());
    }

    public function destroy(int|string $timezone): JsonResponse
    {
        $result = $this->deleteTimezone->execute($timezone);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
