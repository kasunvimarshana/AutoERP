<?php

declare(strict_types=1);

namespace Modules\GS1\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GS1\Application\Contracts\FindGs1IdentifierServiceInterface;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1IdentifierRepositoryInterface;

class FindGs1IdentifierService extends BaseService implements FindGs1IdentifierServiceInterface
{
    public function __construct(private readonly Gs1IdentifierRepositoryInterface $identifierRepository)
    {
        parent::__construct($identifierRepository);
    }

    protected function handle(array $data): mixed
    {
        return $this->identifierRepository->find($data['id'] ?? null);
    }
}
