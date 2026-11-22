<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Constants;

final class UserDocumentType
{
    public const IDENTITY = 'identity';
    public const EMPLOYMENT = 'employment';
    public const CERTIFICATION = 'certification';
    public const OTHER = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::IDENTITY, self::EMPLOYMENT, self::CERTIFICATION, self::OTHER];
    }

    private function __construct() {}
}
