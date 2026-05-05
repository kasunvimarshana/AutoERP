<?php

declare(strict_types=1);

namespace Modules\Service\Application\Contracts;

interface CreateServiceTaskServiceInterface
{
    public function execute(array $data): mixed;
}
