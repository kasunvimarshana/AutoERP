<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

interface UpdateTenantDomainServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): mixed;
}
