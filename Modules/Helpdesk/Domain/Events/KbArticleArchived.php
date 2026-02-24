<?php

namespace Modules\Helpdesk\Domain\Events;

class KbArticleArchived
{
    public function __construct(
        public readonly string $articleId,
        public readonly string $tenantId,
        public readonly string $title,
    ) {}
}
