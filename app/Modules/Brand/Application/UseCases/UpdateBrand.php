<?php

declare(strict_types=1);

namespace Modules\Brand\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Brand\Application\DTOs\BrandData;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Domain\Events\BrandUpdated;
use Modules\Brand\Domain\Exceptions\BrandNotFoundException;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;

class UpdateBrand
{
    public function __construct(private readonly BrandRepositoryInterface $brandRepo) {}

    public function execute(int $id, BrandData $data): Brand
    {
        $brand = $this->brandRepo->find($id);
        if (! $brand) {
            throw new BrandNotFoundException($id);
        }

        $slug = $data->slug ?: Str::slug($data->name);

        $brand->updateDetails(
            name: $data->name,
            slug: $slug,
            description: $data->description,
            website: $data->website,
            attributes: $data->attributes,
            metadata: $data->metadata,
        );

        if (isset($data->status)) {
            if ($data->status === 'active') {
                $brand->activate();
            } elseif ($data->status === 'inactive') {
                $brand->deactivate();
            }
        }

        $saved = $this->brandRepo->save($brand);

        event(new BrandUpdated($saved));

        return $saved;
    }
}
