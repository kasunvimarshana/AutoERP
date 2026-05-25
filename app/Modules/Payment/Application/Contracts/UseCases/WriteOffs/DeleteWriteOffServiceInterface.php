<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\WriteOffs;

use Modules\Core\Application\Results\Result;

interface DeleteWriteOffServiceInterface
{
    public function execute(int|string $id): Result;
}