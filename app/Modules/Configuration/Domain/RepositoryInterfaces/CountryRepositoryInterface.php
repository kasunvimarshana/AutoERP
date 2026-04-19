<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Configuration\Domain\Entities\Country;

interface CountryRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Country;

    public function save(Country $country): Country;
}
