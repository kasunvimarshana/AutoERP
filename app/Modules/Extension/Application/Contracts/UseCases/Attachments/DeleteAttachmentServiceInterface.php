<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\Attachments;

use Modules\Core\Application\Results\Result;

interface DeleteAttachmentServiceInterface
{
    public function execute(int|string $id): Result;
}