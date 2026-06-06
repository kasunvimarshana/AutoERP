<?php

declare(strict_types=1);

namespace Modules\UOM\Constants;

final class UomErrorCode
{
    public const INVALID_VALUE = 'UOM_INVALID_VALUE';

    public const NOT_FOUND = 'UOM_NOT_FOUND';

    public const DUPLICATE_NAME = 'UOM_DUPLICATE_NAME';

    public const DUPLICATE_CONVERSION = 'UOM_DUPLICATE_CONVERSION';

    public const INVALID_FACTOR = 'UOM_INVALID_FACTOR';

    public const SELF_REFERENCE_CONVERSION = 'UOM_SELF_REFERENCE_CONVERSION';

    public const INCOMPATIBLE_UOM_TYPE = 'UOM_INCOMPATIBLE_TYPE';

    public const CONVERSION_NOT_FOUND = 'UOM_CONVERSION_NOT_FOUND';

    public const CANNOT_CONVERT = 'UOM_CANNOT_CONVERT';
}
