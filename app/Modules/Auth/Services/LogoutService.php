<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\LogoutData;
use Modules\Core\Results\Result;

final class LogoutService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function logout(LogoutData $data): Result
    {
        return $this->workflow->logout($data);
    }
}
