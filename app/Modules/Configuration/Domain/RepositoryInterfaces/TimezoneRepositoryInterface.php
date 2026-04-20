<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\RepositoryInterfaces;

use Modules\Configuration\Domain\Entities\Timezone;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface TimezoneRepositoryInterface extends RepositoryInterface
{
    public function findByName(string $name): ?Timezone;

    public function save(Timezone $timezone): Timezone;
}
