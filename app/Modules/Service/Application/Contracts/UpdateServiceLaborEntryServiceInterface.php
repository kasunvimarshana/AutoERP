<?php

declare(strict_types=1);

namespace Modules\Service\Application\Contracts;

interface UpdateServiceLaborEntryServiceInterface
{
    public function execute(array $data): mixed;
}
