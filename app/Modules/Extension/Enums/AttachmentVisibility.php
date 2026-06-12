<?php

declare(strict_types=1);

namespace Modules\Extension\Enums;

enum AttachmentVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Restricted = 'restricted';
}
