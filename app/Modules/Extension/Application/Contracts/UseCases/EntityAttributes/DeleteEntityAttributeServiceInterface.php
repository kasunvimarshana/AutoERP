<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\EntityAttributes;

use Modules\Core\Application\Results\Result;

interface DeleteEntityAttributeServiceInterface
{
    public function execute(int|string $id): Result;
}