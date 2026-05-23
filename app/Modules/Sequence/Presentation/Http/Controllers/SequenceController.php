<?php

declare(strict_types=1);

namespace Modules\Sequence\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sequence\Application\DTOs\SequenceData;
use Modules\Sequence\Application\Services\SequenceService;
use Modules\Sequence\Domain\Exceptions\SequenceRecordNotFoundException;
use Modules\Sequence\Presentation\Http\Controllers\Concerns\HandlesSequenceHttp;
use Modules\Sequence\Presentation\Http\Requests\GenerateSequenceNumberRequest;
use Modules\Sequence\Presentation\Http\Requests\StoreSequenceRequest;
use Modules\Sequence\Presentation\Http\Requests\UpdateSequenceRequest;
use Modules\Sequence\Presentation\Http\Resources\SequenceResource;

class SequenceController extends Controller
{
    use HandlesSequenceHttp;

    public function __construct(private readonly SequenceService $sequences)
    {
    }

    public function index(Request $request): mixed
    {
        return SequenceResource::collection($this->sequences->listSequences(
            $this->filters(
                $request,
                ['tenant_id', 'organization_unit_id', 'document_type', 'period_type', 'period_value'],
            ),
            $this->perPage($request),
        ));
    }

    public function store(StoreSequenceRequest $request): JsonResponse
    {
        $sequence = $this->sequences->createSequence(SequenceData::fromArray($request->validated()));

        return (new SequenceResource($sequence))->response()->setStatusCode(201);
    }

    public function show(int|string $sequence): SequenceResource|JsonResponse
    {
        try {
            return new SequenceResource($this->sequences->findSequence($sequence));
        } catch (SequenceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateSequenceRequest $request, int|string $sequence): SequenceResource|JsonResponse
    {
        try {
            return new SequenceResource(
                $this->sequences->updateSequence($sequence, SequenceData::fromArray($request->validated())),
            );
        } catch (SequenceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $sequence): JsonResponse
    {
        try {
            $this->sequences->deleteSequence($sequence);

            return response()->json(null, 204);
        } catch (SequenceRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function next(GenerateSequenceNumberRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $number = $this->sequences->nextNumber(
            (int) $validated['tenant_id'],
            isset($validated['organization_unit_id']) ? (int) $validated['organization_unit_id'] : null,
            (string) $validated['document_type'],
            isset($validated['period_type']) ? (string) $validated['period_type'] : null,
            isset($validated['at_date']) ? new \DateTimeImmutable((string) $validated['at_date']) : null,
        );

        return response()->json(['number' => $number]);
    }
}
