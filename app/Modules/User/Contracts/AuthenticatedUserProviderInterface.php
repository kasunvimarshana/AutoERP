<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Contracts;

use Modules\Core\DTOs\DataRecord;

interface AuthenticatedUserProviderInterface
{
    public function currentUserRecord(): ?DataRecord;

    public function requireCurrentUserRecord(): DataRecord;
}
