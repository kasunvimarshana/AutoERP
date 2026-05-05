<?php

declare(strict_types=1);

namespace Modules\Service\Application\Contracts;

interface UpdateServiceWarrantyClaimServiceInterface
{
    public function execute(array $data): mixed;
}
