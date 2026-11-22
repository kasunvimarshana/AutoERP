<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Contracts;

interface PermissionDefinitionRegistryInterface
{
    /** @param array<string, string> $descriptions */
    public function register(string $module, array $descriptions): void;

    /** @return array<string, array{module:string,description:string}> */
    public function all(): array;
}
