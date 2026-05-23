<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\DTOs\TimezoneData;
use Modules\Configuration\Application\Services\ConfigurationService;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;
use Modules\Configuration\Presentation\Http\Controllers\Concerns\HandlesConfigurationHttp;
use Modules\Configuration\Presentation\Http\Requests\StoreTimezoneRequest;
use Modules\Configuration\Presentation\Http\Requests\UpdateTimezoneRequest;
use Modules\Configuration\Presentation\Http\Resources\TimezoneResource;

class TimezoneController extends Controller
{
    use HandlesConfigurationHttp;

    public function __construct(private readonly ConfigurationService $configuration) {}

    public function index(Request $request): mixed
    {
        $records = $this->configuration->listTimezones(
            filters: $this->filters($request, ['name', 'offset']),
            perPage: $this->perPage($request),
        );

        return TimezoneResource::collection($records);
    }

    public function store(StoreTimezoneRequest $request): JsonResponse
    {
        $timezone = $this->configuration->createTimezone(TimezoneData::fromArray($request->validated()));

        return (new TimezoneResource($timezone))->response()->setStatusCode(201);
    }

    public function show(int|string $timezone): TimezoneResource|JsonResponse
    {
        try {
            return new TimezoneResource($this->configuration->findTimezone($timezone));
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateTimezoneRequest $request, int|string $timezone): TimezoneResource|JsonResponse
    {
        try {
            return new TimezoneResource($this->configuration->updateTimezone($timezone, TimezoneData::fromArray($request->validated())));
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $timezone): JsonResponse
    {
        try {
            $this->configuration->deleteTimezone($timezone);

            return response()->json(null, 204);
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
