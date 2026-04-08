<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\Events;

use Modules\Tax\Domain\Entities\TaxGroup;

class TaxGroupCreated
{
    public function __construct(
        public readonly TaxGroup $taxGroup,
    ) {}
}
