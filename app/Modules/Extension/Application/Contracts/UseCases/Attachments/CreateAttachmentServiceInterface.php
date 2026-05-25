<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\Attachments;

use Modules\Core\Application\Results\Result;

interface CreateAttachmentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}