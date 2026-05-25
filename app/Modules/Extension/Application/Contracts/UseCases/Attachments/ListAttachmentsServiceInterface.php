<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\Attachments;

use Modules\Core\Application\Results\Result;

interface ListAttachmentsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}