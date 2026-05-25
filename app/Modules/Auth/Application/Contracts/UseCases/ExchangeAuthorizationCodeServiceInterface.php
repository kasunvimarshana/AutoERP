<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\ExchangeAuthorizationCodeData;
use Modules\Core\Application\Results\Result;

interface ExchangeAuthorizationCodeServiceInterface
{
    public function exchangeAuthorizationCode(ExchangeAuthorizationCodeData $data): Result;
}
