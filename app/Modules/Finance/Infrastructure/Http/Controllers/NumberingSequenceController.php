<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\DeleteNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\FindNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\NextNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\UpdateNumberingSequenceServiceInterface;
use Modules\Finance\Domain\Entities\NumberingSequence;
use Modules\Finance\Infrastructure\Http\Requests\ListNumberingSequenceRequest;
use Modules\Finance\Infrastructure\Http\Requests\NextNumberingSequenceRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreNumberingSequenceRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateNumberingSequenceRequest;
use Modules\Finance\Infrastructure\Http\Resources\NumberingSequenceCollection;
use Modules\Finance\Infrastructure\Http\Resources\NumberingSequenceResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NumberingSequenceController extends AuthorizedController
{
    public function __construct(
        private readonly CreateNumberingSequenceServiceInterface $createNumberingSequenceService,
        private readonly UpdateNumberingSequenceServiceInterface $updateNumberingSequenceService,
        private readonly DeleteNumberingSequenceServiceInterface $deleteNumberingSequenceService,
        private readonly FindNumberingSequenceServiceInterface $findNumberingSequenceService,
        private readonly NextNumberingSequenceServiceInterface $nextNumberingSequenceService,
    ) {}

    public function index(ListNumberingSequenceRequest $request): JsonResponse
    {
        $this->authorize('viewAny', NumberingSequence::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'module' => $validated['module'] ?? null,
            'document_type' => $validated['document_type'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $sequences = $this->findNumberingSequenceService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new NumberingSequenceCollection($sequences))->response();
    }

    public function store(StoreNumberingSequenceRequest $request): JsonResponse
    {
        $this->authorize('create', NumberingSequence::class);

        $sequence = $this->createNumberingSequenceService->execute($request->validated());

        return (new NumberingSequenceResource($sequence))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $numberingSequence): NumberingSequenceResource
    {
        $found = $this->findNumberingSequenceOrFail($numberingSequence);
        $this->authorize('view', $found);

        return new NumberingSequenceResource($found);
    }

    public function update(UpdateNumberingSequenceRequest $request, int $numberingSequence): NumberingSequenceResource
    {
        $found = $this->findNumberingSequenceOrFail($numberingSequence);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $numberingSequence;

        return new NumberingSequenceResource($this->updateNumberingSequenceService->execute($payload));
    }

    public function destroy(int $numberingSequence): JsonResponse
    {
        $found = $this->findNumberingSequenceOrFail($numberingSequence);
        $this->authorize('delete', $found);

        $this->deleteNumberingSequenceService->execute(['id' => $numberingSequence]);

        return Response::json(['message' => 'Numbering sequence deleted successfully']);
    }

    public function next(NextNumberingSequenceRequest $request, int $numberingSequence): JsonResponse
    {
        $found = $this->findNumberingSequenceOrFail($numberingSequence);
        $this->authorize('update', $found);

        /** @var array{number: string, sequence: NumberingSequence} $result */
        $result = $this->nextNumberingSequenceService->execute(['id' => $numberingSequence]);

        return Response::json([
            'number' => $result['number'],
            'sequence' => new NumberingSequenceResource($result['sequence']),
        ]);
    }

    private function findNumberingSequenceOrFail(int $id): NumberingSequence
    {
        $sequence = $this->findNumberingSequenceService->find($id);

        if (! $sequence) {
            throw new NotFoundHttpException('Numbering sequence not found.');
        }

        return $sequence;
    }
}
