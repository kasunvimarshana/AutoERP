<?php

namespace Modules\DocumentManagement\Domain\Enums;

enum DocumentStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Archived  = 'archived';
}
