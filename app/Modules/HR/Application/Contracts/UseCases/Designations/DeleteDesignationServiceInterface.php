<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Designations;

use Modules\Core\Application\Results\Result;

interface DeleteDesignationServiceInterface
{
    public function execute(int|string $id): Result;
}