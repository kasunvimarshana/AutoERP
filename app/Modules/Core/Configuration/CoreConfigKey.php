<?php

declare(strict_types=1);

namespace Modules\Core\Configuration;

enum CoreConfigKey: string
{
    case SLUG_FALLBACK = 'core.slug.fallback';
}
