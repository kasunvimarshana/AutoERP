<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Shared\Domain\Entities\Country;

interface CountryRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Country;

    public function save(Country $country): Country;
}
