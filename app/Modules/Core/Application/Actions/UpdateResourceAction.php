<?php

declare(strict_types=1);

namespace Modules\Core\Application\Actions;

use Modules\Core\Application\DTOs\UpdateResourceDto;
use Modules\Core\Domain\Aggregates\ResourceAggregate;
use Modules\Core\Domain\Contracts\Repositories\CrudRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Core\Domain\Services\ResourceDomainService;

final class UpdateResourceAction
{
    /**
     * @param  list<string>  $allowedFields
     */
    public function __construct(
        private readonly CrudRepositoryInterface $repository,
        private readonly ResourceDomainService $domainService,
        private readonly array $allowedFields = [],
    ) {}

    public function execute(UpdateResourceDto $dto): mixed
    {
        if ($this->repository->findById($dto->id) === null) {
            throw new NotFoundException('Record', $dto->id);
        }

        $attributes = $this->domainService->sanitizeAttributes($dto->attributes, $this->allowedFields);
        $aggregate = new ResourceAggregate($attributes);
        $aggregate->validateNotEmpty('Update');

        return $this->repository->updateById($dto->id, $aggregate->attributes());
    }
}
