<?php

namespace Modules\Helpdesk\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Domain\Contracts\KbArticleRepositoryInterface;

class CreateKbArticleUseCase
{
    public function __construct(
        private KbArticleRepositoryInterface $articleRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            return $this->articleRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'category_id' => $data['category_id'] ?? null,
                'title'       => $data['title'],
                'body'        => $data['body'],
                'author_id'   => $data['author_id'] ?? null,
                'tags'        => $data['tags'] ?? null,
                'visibility'  => $data['visibility'] ?? 'agents_only',
                'status'      => 'draft',
            ]);
        });
    }
}
