<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\ReferenceData\Contracts;

interface ReferenceValueLookupInterface
{
    public function supports(string $catalog): bool;
    public function activeValueExists(string $catalog, string|int $value): bool;
}
