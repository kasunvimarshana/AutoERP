<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Enums;

enum OrganisationNodeType: string
{
    case Organisation = 'organisation';
    case Branch = 'branch';
    case Location = 'location';
    case Department = 'department';
}
