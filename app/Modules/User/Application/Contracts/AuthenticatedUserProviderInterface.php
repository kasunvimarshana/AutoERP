<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

use Modules\Core\Application\DTO\DataRecord;

interface AuthenticatedUserProviderInterface
{
    public function currentUserRecord(): ?DataRecord;

    public function requireCurrentUserRecord(): DataRecord;
}
