<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Languages;

use Modules\Core\Application\Results\Result;

interface GetLanguageServiceInterface
{
    public function execute(int|string $id): Result;
}
