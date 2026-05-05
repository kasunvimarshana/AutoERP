<?php

declare(strict_types=1);

namespace Modules\Service\Application\Contracts;

use Modules\Service\Domain\Entities\ServiceJobCard;

interface CompleteServiceJobCardServiceInterface
{
    public function execute(int $tenantId, int $id): ServiceJobCard;
}
