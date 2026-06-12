<?php

declare(strict_types=1);

namespace Modules\Extension\Enums;

enum AttachmentPreviewStatus: string
{
    case Ready = 'ready';
    case Unsupported = 'unsupported';
    case Failed = 'failed';
}
