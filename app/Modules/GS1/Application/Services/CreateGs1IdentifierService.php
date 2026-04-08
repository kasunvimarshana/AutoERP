<?php

declare(strict_types=1);

namespace Modules\GS1\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\GS1\Application\Contracts\CreateGs1IdentifierServiceInterface;
use Modules\GS1\Application\DTOs\Gs1IdentifierData;
use Modules\GS1\Domain\Entities\Gs1Identifier;
use Modules\GS1\Domain\Events\Gs1IdentifierCreated;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1IdentifierRepositoryInterface;

class CreateGs1IdentifierService extends BaseService implements CreateGs1IdentifierServiceInterface
{
    public function __construct(private readonly Gs1IdentifierRepositoryInterface $identifierRepository)
    {
        parent::__construct($identifierRepository);
    }

    protected function handle(array $data): Gs1Identifier
    {
        $dto = Gs1IdentifierData::fromArray($data);

        $identifier = new Gs1Identifier(
            tenantId:        $dto->tenantId,
            identifierType:  $dto->identifierType,
            identifierValue: $dto->identifierValue,
            entityType:      $dto->entityType,
            entityId:        $dto->entityId,
            isActive:        $dto->isActive,
            metadata:        $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->identifierRepository->save($identifier);
        $this->addEvent(new Gs1IdentifierCreated($saved));

        return $saved;
    }
}
