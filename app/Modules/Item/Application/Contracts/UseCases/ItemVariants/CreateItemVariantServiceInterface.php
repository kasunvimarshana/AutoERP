<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemVariants;

use Modules\Core\Application\Results\Result;

interface CreateItemVariantServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
