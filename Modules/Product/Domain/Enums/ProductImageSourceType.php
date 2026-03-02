<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Enums;

/**
 * Discriminates between an externally hosted image URL and a file that has
 * been uploaded to and stored in the platform's configured filesystem disk.
 */
enum ProductImageSourceType: string
{
    /** Image is referenced via an external URL (e.g. a CDN link). */
    case Url = 'url';

    /** Image file was uploaded to and stored in the platform's storage disk. */
    case Upload = 'upload';
}
