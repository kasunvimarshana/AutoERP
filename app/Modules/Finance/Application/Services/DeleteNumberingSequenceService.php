<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteNumberingSequenceServiceInterface;
use Modules\Finance\Domain\Exceptions\NumberingSequenceNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\NumberingSequenceRepositoryInterface;

class DeleteNumberingSequenceService extends BaseService implements DeleteNumberingSequenceServiceInterface
{
    public function __construct(private readonly NumberingSequenceRepositoryInterface $numberingSequenceRepository)
    {
        parent::__construct($numberingSequenceRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $sequence = $this->numberingSequenceRepository->find($id);
        if (! $sequence) {
            throw new NumberingSequenceNotFoundException($id);
        }

        return $this->numberingSequenceRepository->delete($id);
    }
}
