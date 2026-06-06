<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\DTOs\RegistrationData;
use Modules\Core\Application\Results\Result;

final class RegisterService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function register(RegistrationData $data): Result
    {
        return $this->workflow->register($data);
    }
}
