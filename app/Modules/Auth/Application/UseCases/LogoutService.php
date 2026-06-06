<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\DTOs\LogoutData;
use Modules\Core\Application\Results\Result;

final class LogoutService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function logout(LogoutData $data): Result
    {
        return $this->workflow->logout($data);
    }
}
