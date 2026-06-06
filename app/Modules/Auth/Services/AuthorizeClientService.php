<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\AuthorizeClientData;
use Modules\Core\Results\Result;

final class AuthorizeClientService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function authorizeClient(AuthorizeClientData $data): Result
    {
        return $this->workflow->authorizeClient($data);
    }
}
