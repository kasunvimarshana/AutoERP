<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Shared\Domain\Entities\Timezone;

interface TimezoneRepositoryInterface extends RepositoryInterface
{
    public function findByName(string $name): ?Timezone;

    public function save(Timezone $timezone): Timezone;
}
