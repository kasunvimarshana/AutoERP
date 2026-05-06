<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateNumberingSequenceServiceInterface;
use Modules\Finance\Application\DTOs\NumberingSequenceData;
use Modules\Finance\Domain\Entities\NumberingSequence;
use Modules\Finance\Domain\RepositoryInterfaces\NumberingSequenceRepositoryInterface;

class CreateNumberingSequenceService extends BaseService implements CreateNumberingSequenceServiceInterface
{
    public function __construct(private readonly NumberingSequenceRepositoryInterface $numberingSequenceRepository)
    {
        parent::__construct($numberingSequenceRepository);
    }

    protected function handle(array $data): NumberingSequence
    {
        $dto = NumberingSequenceData::fromArray($data);

        $sequence = new NumberingSequence(
            tenantId: $dto->tenantId,
            module: $dto->module,
            documentType: $dto->documentType,
            prefix: $dto->prefix,
            suffix: $dto->suffix,
            nextNumber: $dto->nextNumber,
            padding: $dto->padding,
            isActive: $dto->isActive,
        );

        return $this->numberingSequenceRepository->save($sequence);
    }
}
