<?php

namespace Modules\Item\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Item\Application\Repositories\ComboItemRepositoryInterface;
use Modules\Item\Application\Repositories\ItemAttributeGroupRepositoryInterface;
use Modules\Item\Application\Repositories\ItemAttributeRepositoryInterface;
use Modules\Item\Application\Repositories\ItemAttributeValueRepositoryInterface;
use Modules\Item\Application\Repositories\ItemBrandRepositoryInterface;
use Modules\Item\Application\Repositories\ItemCategoryRepositoryInterface;
use Modules\Item\Application\Repositories\ItemIdentifierRepositoryInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantAttributeRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantAttributeValueRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentComboItemRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemAttributeGroupRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemAttributeRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemAttributeValueRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemBrandRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemCategoryRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemIdentifierRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemVariantAttributeRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemVariantAttributeValueRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemVariantRepository;

class ItemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            ComboItemRepositoryInterface::class => EloquentComboItemRepository::class,
            ItemAttributeGroupRepositoryInterface::class => EloquentItemAttributeGroupRepository::class,
            ItemAttributeRepositoryInterface::class => EloquentItemAttributeRepository::class,
            ItemAttributeValueRepositoryInterface::class => EloquentItemAttributeValueRepository::class,
            ItemBrandRepositoryInterface::class => EloquentItemBrandRepository::class,
            ItemCategoryRepositoryInterface::class => EloquentItemCategoryRepository::class,
            ItemIdentifierRepositoryInterface::class => EloquentItemIdentifierRepository::class,
            ItemRepositoryInterface::class => EloquentItemRepository::class,
            ItemVariantAttributeRepositoryInterface::class => EloquentItemVariantAttributeRepository::class,
            ItemVariantAttributeValueRepositoryInterface::class => EloquentItemVariantAttributeValueRepository::class,
            ItemVariantRepositoryInterface::class => EloquentItemVariantRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
