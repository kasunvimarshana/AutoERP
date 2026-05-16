<?php

declare(strict_types=1);

namespace Modules\Core\Application\Actions;

use Modules\Core\Application\DTOs\CreateResourceDto;
use Modules\Core\Domain\Aggregates\ResourceAggregate;
use Modules\Core\Domain\Contracts\Repositories\CrudRepositoryInterface;
use Modules\Core\Domain\Services\ResourceDomainService;

final class CreateResourceAction
{
    /**
     * @param  list<string>  $allowedFields
     */
    public function __construct(
        private readonly CrudRepositoryInterface $repository,
        private readonly ResourceDomainService $domainService,
        private readonly array $allowedFields = [],
    ) {}

    public function execute(CreateResourceDto $dto): mixed
    {
        $attributes = $this->domainService->sanitizeAttributes($dto->attributes, $this->allowedFields);
        $aggregate = new ResourceAggregate($attributes);
        $aggregate->validateNotEmpty('Create');

        return $this->repository->create($aggregate->attributes());
    }
}
