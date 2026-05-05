<?php

declare(strict_types=1);

namespace Modules\Service\Application\Contracts;

use Modules\Service\Domain\Entities\ServiceJobCard;

interface CreateServiceJobCardServiceInterface
{
    public function execute(array $data): ServiceJobCard;
}
