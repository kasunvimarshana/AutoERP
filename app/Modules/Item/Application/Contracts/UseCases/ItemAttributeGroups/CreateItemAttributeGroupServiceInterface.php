<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups;

use Modules\Core\Application\Results\Result;

interface CreateItemAttributeGroupServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
