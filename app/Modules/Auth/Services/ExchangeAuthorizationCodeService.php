<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\ExchangeAuthorizationCodeData;
use Modules\Core\Results\Result;

final class ExchangeAuthorizationCodeService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function exchangeAuthorizationCode(ExchangeAuthorizationCodeData $data): Result
    {
        return $this->workflow->exchangeAuthorizationCode($data);
    }
}
