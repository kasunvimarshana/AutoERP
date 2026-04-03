<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Returns\Application\Contracts\CreateCreditMemoServiceInterface;
use Modules\Returns\Application\DTOs\CreditMemoData;
use Modules\Returns\Domain\Entities\CreditMemo;
use Modules\Returns\Domain\Events\CreditMemoCreated;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class CreateCreditMemoService extends BaseService implements CreateCreditMemoServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    protected function handle(array $data): CreditMemo
    {
        $dto = CreditMemoData::fromArray($data);

        $memo = new CreditMemo(
            tenantId:        $dto->tenantId,
            referenceNumber: $dto->referenceNumber,
            partyId:         $dto->partyId,
            partyType:       $dto->partyType,
            stockReturnId:   $dto->stockReturnId,
            amount:          $dto->amount,
            currency:        $dto->currency,
            notes:           $dto->notes,
            metadata:        $dto->metadata ? new Metadata($dto->metadata) : null,
            status:          $dto->status,
        );

        $saved = $this->creditMemoRepository->save($memo);
        $this->addEvent(new CreditMemoCreated($saved));

        return $saved;
    }
}
