<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Returns\Application\Contracts\CreateReturnAuthorizationServiceInterface;
use Modules\Returns\Application\DTOs\ReturnAuthorizationData;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\Events\ReturnAuthorizationCreated;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;

class CreateReturnAuthorizationService extends BaseService implements CreateReturnAuthorizationServiceInterface
{
    public function __construct(private readonly ReturnAuthorizationRepositoryInterface $authorizationRepository)
    {
        parent::__construct($authorizationRepository);
    }

    protected function handle(array $data): ReturnAuthorization
    {
        $dto = ReturnAuthorizationData::fromArray($data);

        $expiresAt = $dto->expiresAt ? new \DateTimeImmutable($dto->expiresAt) : null;

        $authorization = new ReturnAuthorization(
            tenantId:   $dto->tenantId,
            rmaNumber:  $dto->rmaNumber,
            returnType: $dto->returnType,
            partyId:    $dto->partyId,
            partyType:  $dto->partyType,
            reason:     $dto->reason,
            status:     $dto->status,
            expiresAt:  $expiresAt,
            notes:      $dto->notes,
            metadata:   $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->authorizationRepository->save($authorization);
        $this->addEvent(new ReturnAuthorizationCreated($saved));

        return $saved;
    }
}
