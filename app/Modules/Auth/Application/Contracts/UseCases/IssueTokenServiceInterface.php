<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\TokenIssueData;
use Modules\Core\Application\Results\Result;

interface IssueTokenServiceInterface
{
    public function issueToken(TokenIssueData $data): Result;
}
