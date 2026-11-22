<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Contracts;

interface PlatformOperatorCredentialProvisionerInterface
{
    /** @return array{minimum_length:int,mixed_case:bool,numbers:bool,symbols:bool} */
    public function passwordRequirements(): array;

    public function provision(int $platformOperatorId, string $plainPassword): void;
}
