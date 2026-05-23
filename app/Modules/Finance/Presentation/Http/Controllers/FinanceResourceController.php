<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Application\DTOs\FinanceRecordData;
use Modules\Finance\Application\Services\FinanceService;
use Modules\Finance\Domain\Exceptions\FinanceIntegrityException;
use Modules\Finance\Domain\Exceptions\FinanceRecordNotFoundException;
use Modules\Finance\Presentation\Http\Requests\FinanceRecordRequest;
use Modules\Finance\Presentation\Http\Resources\FinanceRecordResource;

class FinanceResourceController extends Controller
{
    public function __construct(private readonly FinanceService $finance) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return FinanceRecordResource::collection($this->finance->list($resource, $tenant, $this->perPage($request)));
        } catch (FinanceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(FinanceRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->finance->create($resource, FinanceRecordData::fromArray($tenant, $request->validated()));

            return (new FinanceRecordResource($record))->response()->setStatusCode(201);
        } catch (FinanceIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (FinanceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): FinanceRecordResource|JsonResponse
    {
        try {
            return new FinanceRecordResource($this->finance->find($resource, $tenant, $id));
        } catch (FinanceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(FinanceRecordRequest $request, int|string $tenant, string $resource, int|string $id): FinanceRecordResource|JsonResponse
    {
        try {
            return new FinanceRecordResource($this->finance->update($resource, $tenant, $id, FinanceRecordData::fromArray($tenant, $request->validated())));
        } catch (FinanceIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (FinanceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->finance->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (FinanceIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (FinanceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(FinanceRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(FinanceIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
