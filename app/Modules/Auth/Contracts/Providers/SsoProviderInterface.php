<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts\Providers;

use Modules\Auth\DTOs\AuthorizeClientData;
use Modules\Auth\DTOs\ExchangeAuthorizationCodeData;

interface SsoProviderInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function authorizeClient(AuthorizeClientData $data): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function exchangeAuthorizationCode(ExchangeAuthorizationCodeData $data): ?array;
}
