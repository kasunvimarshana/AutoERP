<?php

declare(strict_types=1);

namespace Modules\Sequence\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\CreateSequenceServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\DeleteSequenceServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\GenerateSequenceNumberServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\GetSequenceServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\ListSequencesServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\PreviewSequenceNumberServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\RollbackSequenceNumberServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\UpdateSequenceServiceInterface;
use Modules\Sequence\Presentation\Http\Requests\GenerateSequenceNumberRequest;
use Modules\Sequence\Presentation\Http\Requests\ListSequenceRequest;
use Modules\Sequence\Presentation\Http\Requests\PreviewSequenceNumberRequest;
use Modules\Sequence\Presentation\Http\Requests\RollbackSequenceNumberRequest;
use Modules\Sequence\Presentation\Http\Requests\UpsertSequenceRequest;
use Modules\Sequence\Presentation\Http\Resources\SequenceResource;

final class SequenceController extends Controller
{
    public function __construct(
        private readonly ListSequencesServiceInterface $listSequences,
        private readonly GetSequenceServiceInterface $getSequence,
        private readonly CreateSequenceServiceInterface $createSequence,
        private readonly UpdateSequenceServiceInterface $updateSequence,
        private readonly DeleteSequenceServiceInterface $deleteSequence,
        private readonly PreviewSequenceNumberServiceInterface $previewSequenceNumber,
        private readonly GenerateSequenceNumberServiceInterface $generateSequenceNumber,
        private readonly RollbackSequenceNumberServiceInterface $rollbackSequenceNumber,
    ) {
    }

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
