<?php

namespace Modules\Helpdesk\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Domain\Contracts\KbArticleRepositoryInterface;

class RateKbArticleUseCase
{
    public function __construct(
        private KbArticleRepositoryInterface $articleRepo,
    ) {}

    /**
     * Record a helpful/not-helpful vote on a published KB article.
     *
     * @param  string  $id       Article UUID
     * @param  bool    $helpful  true = helpful, false = not helpful
     */
    public function execute(string $id, bool $helpful): object
    {
        return DB::transaction(function () use ($id, $helpful) {
            $article = $this->articleRepo->findById($id);

            if (! $article) {
                throw new DomainException('Knowledge base article not found.');
            }

            if ($article->status !== 'published') {
                throw new DomainException('Only published articles can be rated.');
            }

            $increment = $helpful ? 'helpful_count' : 'not_helpful_count';

            return $this->articleRepo->update($id, [
                $increment => $article->{$increment} + 1,
            ]);
        });
    }
}
