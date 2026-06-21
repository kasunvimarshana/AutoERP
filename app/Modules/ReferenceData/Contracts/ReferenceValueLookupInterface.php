<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Contracts;

interface ReferenceValueLookupInterface
{
    public function supports(string $catalog): bool;
    public function activeValueExists(string $catalog, string|int $value): bool;
}
