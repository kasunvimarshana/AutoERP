<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\UseCases\AuthorizeClientServiceInterface;
use Modules\Auth\Application\DTOs\AuthorizeClientData;
use Modules\Core\Application\Results\Result;

final class AuthorizeClientService implements AuthorizeClientServiceInterface
{
    public function __construct(private readonly AuthWorkflowService $workflow)
    {
    }

    public function authorizeClient(AuthorizeClientData $data): Result
    {
        return $this->workflow->authorizeClient($data);
    }
}
