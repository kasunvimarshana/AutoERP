<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\OrganizationUnit\Data;

final readonly class OrganizationUnitBrandingProfile
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $logoDataUri,
    ) {}
}
