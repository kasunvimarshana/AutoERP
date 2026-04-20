<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\RepositoryInterfaces;

use Modules\Configuration\Domain\Entities\Country;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface CountryRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Country;

    public function save(Country $country): Country;
}
