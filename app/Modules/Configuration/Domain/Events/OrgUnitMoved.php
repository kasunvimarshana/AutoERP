<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Events;

use Modules\Configuration\Domain\Entities\OrgUnit;

class OrgUnitMoved
{
    public function __construct(
        public readonly OrgUnit $orgUnit,
        public readonly ?string $previousParentId,
    ) {}
}
