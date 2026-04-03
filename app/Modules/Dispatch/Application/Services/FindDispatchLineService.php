<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Dispatch\Application\Contracts\FindDispatchLineServiceInterface;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;

class FindDispatchLineService extends BaseService implements FindDispatchLineServiceInterface
{
    public function __construct(private readonly DispatchLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    public function findByDispatch(int $dispatchId): Collection
    {
        return $this->lineRepository->findByDispatch($dispatchId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
