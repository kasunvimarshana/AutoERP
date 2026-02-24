<?php

namespace Modules\Helpdesk\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Domain\Contracts\KbArticleRepositoryInterface;
use Modules\Helpdesk\Domain\Events\KbArticlePublished;

class PublishKbArticleUseCase
{
    public function __construct(
        private KbArticleRepositoryInterface $articleRepo,
    ) {}

    public function execute(string $id): object
    {
        return DB::transaction(function () use ($id) {
            $article = $this->articleRepo->findById($id);

            if (! $article) {
                throw new DomainException('Knowledge base article not found.');
            }

            if ($article->status === 'published') {
                throw new DomainException('Article is already published.');
            }

            if ($article->status === 'archived') {
                throw new DomainException('Archived articles cannot be published.');
            }

            $article = $this->articleRepo->update($id, [
                'status'       => 'published',
                'published_at' => now(),
            ]);

            Event::dispatch(new KbArticlePublished(
                $article->id,
                $article->tenant_id,
                $article->title,
            ));

            return $article;
        });
    }
}
