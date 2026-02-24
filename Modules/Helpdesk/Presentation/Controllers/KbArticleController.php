<?php

namespace Modules\Helpdesk\Presentation\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Application\UseCases\ArchiveKbArticleUseCase;
use Modules\Helpdesk\Application\UseCases\CreateKbArticleUseCase;
use Modules\Helpdesk\Application\UseCases\PublishKbArticleUseCase;
use Modules\Helpdesk\Application\UseCases\RateKbArticleUseCase;
use Modules\Helpdesk\Domain\Contracts\KbArticleRepositoryInterface;
use Modules\Helpdesk\Presentation\Requests\RateKbArticleRequest;
use Modules\Helpdesk\Presentation\Requests\StoreKbArticleRequest;

class KbArticleController extends Controller
{
    public function __construct(
        private KbArticleRepositoryInterface $articleRepo,
        private CreateKbArticleUseCase       $createUseCase,
        private PublishKbArticleUseCase      $publishUseCase,
        private ArchiveKbArticleUseCase      $archiveUseCase,
        private RateKbArticleUseCase         $rateUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->articleRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreKbArticleRequest $request): JsonResponse
    {
        $article = $this->createUseCase->execute(
            array_merge($request->validated(), [
                'tenant_id' => auth()->user()?->tenant_id,
                'author_id' => auth()->id(),
            ])
        );

        return response()->json($article, 201);
    }

    public function show(string $id): JsonResponse
    {
        $article = $this->articleRepo->findById($id);

        if (! $article) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($article);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->articleRepo->delete($id);

        return response()->json(null, 204);
    }

    public function publish(string $id): JsonResponse
    {
        try {
            $article = $this->publishUseCase->execute($id);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($article);
    }

    public function archive(string $id): JsonResponse
    {
        try {
            $article = $this->archiveUseCase->execute($id);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($article);
    }

    public function rate(RateKbArticleRequest $request, string $id): JsonResponse
    {
        try {
            $article = $this->rateUseCase->execute($id, (bool) $request->validated('helpful'));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($article);
    }

    /**
     * Public endpoint — returns published articles visible to everyone.
     * Does not require authentication.
     */
    public function publicIndex(string $tenantId): JsonResponse
    {
        return response()->json(
            $this->articleRepo->findPublished($tenantId)
        );
    }
}
