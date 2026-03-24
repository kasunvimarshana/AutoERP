<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

interface WriteServiceInterface
{
    public function execute(array $data = []): mixed;
}
