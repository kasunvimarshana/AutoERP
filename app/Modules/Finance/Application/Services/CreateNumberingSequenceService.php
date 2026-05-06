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
            tenantId: $dto->tenant_id,
            module: $dto->module,
            documentType: $dto->document_type,
            prefix: $dto->prefix,
            suffix: $dto->suffix,
            nextNumber: $dto->next_number,
            padding: $dto->padding,
            isActive: $dto->is_active,
        );

        return $this->numberingSequenceRepository->save($sequence);
    }
}
