<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Contracts;

use Modules\Core\DTOs\CurrentUserContext;

interface CurrentUserContextAccessorInterface
{
    public function current(): ?CurrentUserContext;

    public function requireCurrent(): CurrentUserContext;

    public function currentUserId(): ?int;

    public function currentApplicationId(): ?string;
}
