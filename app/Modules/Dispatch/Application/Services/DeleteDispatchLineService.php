<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Dispatch\Application\Contracts\DeleteDispatchLineServiceInterface;
use Modules\Dispatch\Domain\Events\DispatchLineDeleted;
use Modules\Dispatch\Domain\Exceptions\DispatchLineNotFoundException;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;

class DeleteDispatchLineService extends BaseService implements DeleteDispatchLineServiceInterface
{
    public function __construct(private readonly DispatchLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): bool
    {
        $id   = $data['id'];
        $line = $this->lineRepository->find($id);

        if (! $line) {
            throw new DispatchLineNotFoundException($id);
        }

        $this->addEvent(new DispatchLineDeleted($line->getId(), $line->getDispatchId()));

        return $this->lineRepository->delete($id);
    }
}
