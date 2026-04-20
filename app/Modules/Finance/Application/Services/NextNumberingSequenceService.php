<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\NextNumberingSequenceServiceInterface;
use Modules\Finance\Domain\Exceptions\NumberingSequenceNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\NumberingSequenceRepositoryInterface;

class NextNumberingSequenceService extends BaseService implements NextNumberingSequenceServiceInterface
{
    public function __construct(private readonly NumberingSequenceRepositoryInterface $numberingSequenceRepository)
    {
        parent::__construct($numberingSequenceRepository);
    }

    protected function handle(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);

        $sequence = $this->numberingSequenceRepository->find($id);
        if (! $sequence) {
            throw new NumberingSequenceNotFoundException($id);
        }

        if (! $sequence->isActive()) {
            throw new DomainException('Numbering sequence is not active.');
        }

        $number = $sequence->generateNext();

        $this->numberingSequenceRepository->save($sequence);

        return ['number' => $number, 'sequence' => $sequence];
    }
}
