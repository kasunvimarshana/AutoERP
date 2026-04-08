<?php

declare(strict_types=1);

namespace Modules\GS1\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\GS1\Application\Contracts\UpdateGs1IdentifierServiceInterface;
use Modules\GS1\Application\DTOs\UpdateGs1IdentifierData;
use Modules\GS1\Domain\Entities\Gs1Identifier;
use Modules\GS1\Domain\Events\Gs1IdentifierUpdated;
use Modules\GS1\Domain\Exceptions\Gs1IdentifierNotFoundException;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1IdentifierRepositoryInterface;

class UpdateGs1IdentifierService extends BaseService implements UpdateGs1IdentifierServiceInterface
{
    public function __construct(private readonly Gs1IdentifierRepositoryInterface $identifierRepository)
    {
        parent::__construct($identifierRepository);
    }

    protected function handle(array $data): Gs1Identifier
    {
        $dto = UpdateGs1IdentifierData::fromArray($data);

        /** @var Gs1Identifier|null $identifier */
        $identifier = $this->identifierRepository->find($dto->id);
        if (! $identifier) {
            throw new Gs1IdentifierNotFoundException($dto->id);
        }

        $identifier->updateDetails(
            identifierType:  $dto->identifierType ?? $identifier->getIdentifierType(),
            identifierValue: $dto->identifierValue ?? $identifier->getIdentifierValue(),
            entityType:      $dto->entityType ?? $identifier->getEntityType(),
            entityId:        $dto->entityId ?? $identifier->getEntityId(),
            isActive:        $dto->isActive ?? $identifier->isActive(),
            metadata:        $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->identifierRepository->save($identifier);
        $this->addEvent(new Gs1IdentifierUpdated($saved));

        return $saved;
    }
}
