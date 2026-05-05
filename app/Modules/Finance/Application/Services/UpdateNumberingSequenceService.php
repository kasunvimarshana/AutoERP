<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Application\Contracts\UpdateNumberingSequenceServiceInterface;
use Modules\Finance\Application\DTOs\NumberingSequenceData;
use Modules\Finance\Domain\Entities\NumberingSequence;
use Modules\Finance\Domain\Exceptions\NumberingSequenceNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\NumberingSequenceRepositoryInterface;

class UpdateNumberingSequenceService extends BaseService implements UpdateNumberingSequenceServiceInterface
{
    public function __construct(private readonly NumberingSequenceRepositoryInterface $numberingSequenceRepository)
    {
        parent::__construct($numberingSequenceRepository);
    }

    protected function handle(array $data): NumberingSequence
    {
        $dto = NumberingSequenceData::fromArray($data);

        /** @var NumberingSequence|null $sequence */
        $sequence = $this->numberingSequenceRepository->find((int) $dto->id);
        if (! $sequence) {
            throw new NumberingSequenceNotFoundException((int) $dto->id);
        }
        if ($dto->row_version !== $sequence->getRowVersion()) {
            throw new ConcurrentModificationException('NumberingSequence', (int) $dto->id);
        }

        $sequence->update(
            prefix: $dto->prefix,
            suffix: $dto->suffix,
            padding: $dto->padding,
            isActive: $dto->is_active,
        );

        return $this->numberingSequenceRepository->save($sequence);
    }
}
