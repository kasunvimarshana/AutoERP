<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Configuration\Domain\Entities\Currency;

interface CurrencyRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Currency;

    public function save(Currency $currency): Currency;
}
