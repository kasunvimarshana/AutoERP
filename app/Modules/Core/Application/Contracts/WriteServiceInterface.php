<?php

namespace Modules\Core\Application\Contracts;

interface WriteServiceInterface
{
    public function execute(array $data = []): mixed;
}
