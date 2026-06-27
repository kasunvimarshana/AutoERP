<?php

declare(strict_types=1);

namespace Modules\Sequence\Constants;

final class SequencePermission
{
    public const VIEW = 'sequences.view';
    public const MANAGE = 'sequences.manage';
    public const GENERATE = 'sequences.generate';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View number-sequence definitions and previews.',
            self::MANAGE => 'Create and maintain number-sequence definitions.',
            self::GENERATE => 'Generate governed document numbers.',
        ];
    }
}
