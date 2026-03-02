<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Enums;

enum OrganisationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
