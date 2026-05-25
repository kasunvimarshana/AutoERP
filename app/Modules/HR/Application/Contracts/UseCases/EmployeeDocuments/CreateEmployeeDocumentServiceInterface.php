<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\EmployeeDocuments;

use Modules\Core\Application\Results\Result;

interface CreateEmployeeDocumentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}