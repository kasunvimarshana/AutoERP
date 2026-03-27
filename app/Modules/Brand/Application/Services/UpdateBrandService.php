<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Services;

use Illuminate\Support\Str;
use Modules\Brand\Application\Contracts\UpdateBrandServiceInterface;
use Modules\Brand\Application\DTOs\BrandData;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Domain\Events\BrandUpdated;
use Modules\Brand\Domain\Exceptions\BrandNotFoundException;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

class UpdateBrandService extends BaseService implements UpdateBrandServiceInterface
{
    public function __construct(private readonly BrandRepositoryInterface $brandRepository)
    {
        parent::__construct($brandRepository);
    }

    protected function handle(array $data): Brand
    {
        $id = $data['id'];
        $brand = $this->brandRepository->find($id);

        if (! $brand) {
            throw new BrandNotFoundException($id);
        }

        $dto = BrandData::fromArray($data);
        $slug = $dto->slug ?: Str::slug($dto->name);

        $brand->updateDetails(
            name: $dto->name,
            slug: $slug,
            description: $dto->description,
            website: $dto->website,
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        if (isset($dto->status)) {
            if ($dto->status === 'active') {
                $brand->activate();
            } elseif ($dto->status === 'inactive') {
                $brand->deactivate();
            }
        }

        $saved = $this->brandRepository->save($brand);

        $this->addEvent(new BrandUpdated($saved));

        return $saved;
    }
}
