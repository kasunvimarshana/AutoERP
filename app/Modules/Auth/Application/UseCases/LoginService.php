<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\DTOs\LoginData;
use Modules\Core\Application\Results\Result;

final class LoginService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function login(LoginData $data): Result
    {
        return $this->workflow->login($data);
    }
}
