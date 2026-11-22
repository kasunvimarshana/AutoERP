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

use Illuminate\Http\Request;
use Modules\Core\DTOs\CurrentUserContext;

interface CurrentUserContextResolverInterface
{
    public function resolve(Request $request): ?CurrentUserContext;
}
