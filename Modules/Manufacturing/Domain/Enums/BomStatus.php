<?php

namespace Modules\Manufacturing\Domain\Enums;

enum BomStatus: string
{
    case DRAFT    = 'draft';
    case ACTIVE   = 'active';
    case OBSOLETE = 'obsolete';
}
