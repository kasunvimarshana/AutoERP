<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteComboItemServiceInterface;
use Modules\Product\Domain\Exceptions\ComboItemNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;

class DeleteComboItemService extends BaseService implements DeleteComboItemServiceInterface
{
    public function __construct(private readonly ComboItemRepositoryInterface $comboItemRepository)
    {
        parent::__construct($comboItemRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->comboItemRepository->find($id);

        if (! $entity) {
            throw new ComboItemNotFoundException($id);
        }

        return $this->comboItemRepository->delete($id);
    }
}
