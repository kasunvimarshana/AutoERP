<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Auth\Exceptions;

use Modules\Auth\Models\AuthRefreshTokenModel;
use RuntimeException;

final class AuthFamilyCompromiseRequested extends RuntimeException
{
    public function __construct(
        public readonly AuthRefreshTokenModel $refreshToken,
        public readonly string $reason,
    ) {
        parent::__construct($reason);
    }
}
