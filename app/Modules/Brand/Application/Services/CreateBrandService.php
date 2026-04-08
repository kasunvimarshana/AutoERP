<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Services;

use Illuminate\Support\Str;
use Modules\Brand\Application\Contracts\CreateBrandServiceInterface;
use Modules\Brand\Application\DTOs\BrandData;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Domain\Events\BrandCreated;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

class CreateBrandService extends BaseService implements CreateBrandServiceInterface
{
    public function __construct(private readonly BrandRepositoryInterface $brandRepository)
    {
        parent::__construct($brandRepository);
    }

    protected function handle(array $data): Brand
    {
        $dto = BrandData::fromArray($data);

        $slug = $dto->slug ?: Str::slug($dto->name);

        $brand = new Brand(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            slug: $slug,
            description: $dto->description,
            website: $dto->website,
            status: $dto->status ?? 'active',
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        $saved = $this->brandRepository->save($brand);

        $this->addEvent(new BrandCreated($saved));

        return $saved;
    }
}
