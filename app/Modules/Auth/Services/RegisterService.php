<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\RegistrationData;
use Modules\Core\Results\Result;

final class RegisterService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function register(RegistrationData $data): Result
    {
        return $this->workflow->register($data);
    }
}
