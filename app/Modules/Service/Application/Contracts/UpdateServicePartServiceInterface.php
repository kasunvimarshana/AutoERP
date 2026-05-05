<?php

declare(strict_types=1);

namespace Modules\Service\Application\Contracts;

interface UpdateServicePartServiceInterface
{
    public function execute(array $data): mixed;
}
