<?php

declare(strict_types=1);

namespace Modules\Sequence\Constants;

final class SequenceErrorCode
{
    public const NOT_FOUND = 'SEQUENCE_NOT_FOUND';

    public const INVALID_VALUE = 'SEQUENCE_INVALID_VALUE';

    public const CONFLICT = 'SEQUENCE_CONFLICT';

    public const CONCURRENCY_CONFLICT = 'SEQUENCE_CONCURRENCY_CONFLICT';

    public const INTERNAL_ERROR = 'SEQUENCE_INTERNAL_ERROR';

    private function __construct() {}
}
