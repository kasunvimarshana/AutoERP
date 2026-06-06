<?php

declare(strict_types=1);

namespace Modules\Core\Configuration;

enum CoreConfigKey: string
{
    case FILE_STORAGE_DEFAULT_DISK = 'core.file_storage.default_disk';
    case SLUG_FALLBACK = 'core.slug.fallback';
}
