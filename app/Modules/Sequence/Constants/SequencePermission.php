<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Sequence\Constants;

final class SequencePermission
{
    public const VIEW = 'sequences.view';
    public const MANAGE = 'sequences.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View sequence definitions and preview next sequence numbers.',
            self::MANAGE => 'Create, update, delete, and generate document numbering sequences.',
        ];
    }

    private function __construct() {}
}
