<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteVariantAttributeServiceInterface;
use Modules\Product\Domain\Exceptions\VariantAttributeNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\VariantAttributeRepositoryInterface;

class DeleteVariantAttributeService extends BaseService implements DeleteVariantAttributeServiceInterface
{
    public function __construct(private readonly VariantAttributeRepositoryInterface $variantAttributeRepository)
    {
        parent::__construct($variantAttributeRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $variantAttribute = $this->variantAttributeRepository->find($id);

        if (! $variantAttribute) {
            throw new VariantAttributeNotFoundException($id);
        }

        return $this->variantAttributeRepository->delete($id);
    }
}
