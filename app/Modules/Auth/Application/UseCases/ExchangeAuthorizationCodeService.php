<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\UseCases\ExchangeAuthorizationCodeServiceInterface;
use Modules\Auth\Application\DTOs\ExchangeAuthorizationCodeData;
use Modules\Core\Application\Results\Result;

final class ExchangeAuthorizationCodeService implements ExchangeAuthorizationCodeServiceInterface
{
    public function __construct(private readonly AuthWorkflowService $workflow)
    {
    }

    public function exchangeAuthorizationCode(ExchangeAuthorizationCodeData $data): Result
    {
        return $this->workflow->exchangeAuthorizationCode($data);
    }
}
