<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Languages;

use Modules\Core\Application\Results\Result;

interface CreateLanguageServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
