<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface IsFeatureEnabledServiceInterface
{
    public function execute(string $key, ?int $tenantId = null, bool $default = false): Result;
}
