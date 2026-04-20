<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindNumberingSequenceServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\NumberingSequenceRepositoryInterface;

class FindNumberingSequenceService extends BaseService implements FindNumberingSequenceServiceInterface
{
    public function __construct(private readonly NumberingSequenceRepositoryInterface $numberingSequenceRepository)
    {
        parent::__construct($numberingSequenceRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
