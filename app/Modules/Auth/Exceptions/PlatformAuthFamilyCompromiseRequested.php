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

use Modules\Auth\Models\AuthPlatformRefreshTokenModel;
use RuntimeException;

final class PlatformAuthFamilyCompromiseRequested extends RuntimeException
{
    public function __construct(
        public readonly AuthPlatformRefreshTokenModel $refreshToken,
        public readonly string $reason,
    ) {
        parent::__construct($reason);
    }
}
