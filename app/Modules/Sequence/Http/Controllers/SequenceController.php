<?php

declare(strict_types=1);

namespace Modules\Sequence\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Sequence\Http\Requests\GenerateSequenceNumberRequest;
use Modules\Sequence\Http\Requests\ListSequenceRequest;
use Modules\Sequence\Http\Requests\PreviewSequenceNumberRequest;
use Modules\Sequence\Http\Requests\RollbackSequenceNumberRequest;
use Modules\Sequence\Http\Requests\UpsertSequenceRequest;
use Modules\Sequence\Http\Resources\SequenceResource;
use Modules\Sequence\Services\Sequences\CreateSequenceService;
use Modules\Sequence\Services\Sequences\DeleteSequenceService;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use Modules\Sequence\Services\Sequences\GetSequenceService;
use Modules\Sequence\Services\Sequences\ListSequencesService;
use Modules\Sequence\Services\Sequences\PreviewSequenceNumberService;
use Modules\Sequence\Services\Sequences\RollbackSequenceNumberService;
use Modules\Sequence\Services\Sequences\UpdateSequenceService;

final class SequenceController extends Controller
{
    public function __construct(
        private readonly ListSequencesService $listSequences,
        private readonly GetSequenceService $getSequence,
        private readonly CreateSequenceService $createSequence,
        private readonly UpdateSequenceService $updateSequence,
        private readonly DeleteSequenceService $deleteSequence,
        private readonly PreviewSequenceNumberService $previewSequenceNumber,
        private readonly GenerateSequenceNumberService $generateSequenceNumber,
        private readonly RollbackSequenceNumberService $rollbackSequenceNumber,
    ) {}

    public function index(ListSequenceRequest $request): JsonResponse
    {
        $result = $this->listSequences->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => SequenceResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $sequence): JsonResponse|SequenceResource
    {
        $result = $this->getSequence->execute($sequence);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SequenceResource($result->valueOrFail());
    }

    public function store(UpsertSequenceRequest $request): JsonResponse|SequenceResource
    {
        $result = $this->createSequence->execute($request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SEQUENCE_CONFLICT' ? 409 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return (new SequenceResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSequenceRequest $request, int|string $sequence): JsonResponse|SequenceResource
    {
        $result = $this->updateSequence->execute($sequence, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = match ($error->code) {
                'SEQUENCE_NOT_FOUND' => 404,
                'SEQUENCE_CONFLICT' => 409,
                default => 422,
            };

            return response()->json(['message' => $error->message], $status);
        }

        return new SequenceResource($result->valueOrFail());
    }

    public function destroy(int|string $sequence): JsonResponse
    {
        $result = $this->deleteSequence->execute($sequence);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    public function previewNumber(PreviewSequenceNumberRequest $request): JsonResponse
    {
        $result = $this->previewSequenceNumber->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json($result->valueOrFail());
    }

    public function generateNumber(GenerateSequenceNumberRequest $request): JsonResponse
    {
        $result = $this->generateSequenceNumber->execute($request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SEQUENCE_CONCURRENCY_CONFLICT' ? 409 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json($result->valueOrFail());
    }

    public function rollbackNumber(RollbackSequenceNumberRequest $request): JsonResponse
    {
        $result = $this->rollbackSequenceNumber->execute($request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = match ($error->code) {
                'SEQUENCE_NOT_FOUND' => 404,
                'SEQUENCE_CONCURRENCY_CONFLICT' => 409,
                default => 422,
            };

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json($result->valueOrFail());
    }
}
