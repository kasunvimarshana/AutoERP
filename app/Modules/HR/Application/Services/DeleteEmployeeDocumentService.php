<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeleteEmployeeDocumentServiceInterface;
use Modules\HR\Domain\Exceptions\EmployeeDocumentNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeDocumentRepositoryInterface;

class DeleteEmployeeDocumentService extends BaseService implements DeleteEmployeeDocumentServiceInterface
{
    public function __construct(
        private readonly EmployeeDocumentRepositoryInterface $documentRepository,
    ) {
        parent::__construct($this->documentRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $document = $this->documentRepository->find($id);

        if ($document === null) {
            throw new EmployeeDocumentNotFoundException($id);
        }

        return $this->documentRepository->delete($id);
    }
}
