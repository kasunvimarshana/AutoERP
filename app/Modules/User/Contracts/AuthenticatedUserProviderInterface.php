<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

use Modules\Core\DTOs\DataRecord;

interface AuthenticatedUserProviderInterface
{
    public function currentUserRecord(): ?DataRecord;

    public function requireCurrentUserRecord(): DataRecord;
}
