<?php

declare(strict_types=1);

namespace Modules\Service\Application\Contracts;

use Modules\Service\Domain\Entities\ServiceJobCard;

interface UpdateServiceJobCardStatusServiceInterface
{
    public function execute(int $tenantId, int $id, string $status): ServiceJobCard;
}
