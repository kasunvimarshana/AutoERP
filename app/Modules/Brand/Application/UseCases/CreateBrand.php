<?php

declare(strict_types=1);

namespace Modules\Brand\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Brand\Application\DTOs\BrandData;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Domain\Events\BrandCreated;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;

class CreateBrand
{
    public function __construct(private readonly BrandRepositoryInterface $brandRepo) {}

    public function execute(BrandData $data): Brand
    {
        $slug = $data->slug ?: Str::slug($data->name);

        $brand = new Brand(
            tenantId: $data->tenant_id,
            name: $data->name,
            slug: $slug,
            description: $data->description,
            website: $data->website,
            status: $data->status ?? 'active',
            attributes: $data->attributes,
            metadata: $data->metadata,
        );

        $saved = $this->brandRepo->save($brand);

        event(new BrandCreated($saved));

        return $saved;
    }
}
