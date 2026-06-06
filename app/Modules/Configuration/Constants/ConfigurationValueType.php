<?php

declare(strict_types=1);

namespace Modules\Configuration\Constants;

final class ConfigurationValueType
{
    public const STRING = 'string';

    public const INTEGER = 'integer';

    public const FLOAT = 'float';

    public const BOOLEAN = 'boolean';

    public const JSON = 'json';

    public const NULL = 'null';

    public const ENCRYPTED = 'encrypted';

    private function __construct() {}
}
