<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

interface DeleteTenantDomainServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): mixed;
}
