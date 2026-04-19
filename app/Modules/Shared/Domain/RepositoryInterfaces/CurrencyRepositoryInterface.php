<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Shared\Domain\Entities\Currency;

interface CurrencyRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Currency;

    public function save(Currency $currency): Currency;
}
