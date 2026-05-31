<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Item\Application\Contracts\UseCases\ComboItems\CreateComboItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ComboItems\DeleteComboItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ComboItems\GetComboItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ComboItems\ListComboItemsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ComboItems\UpdateComboItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\CreateItemAttributeGroupServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\DeleteItemAttributeGroupServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\GetItemAttributeGroupServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\ListItemAttributeGroupsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\UpdateItemAttributeGroupServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\CreateItemAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\DeleteItemAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\GetItemAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\ListItemAttributesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\UpdateItemAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\CreateItemAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\DeleteItemAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\GetItemAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\ListItemAttributeValuesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\UpdateItemAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\CreateItemBrandServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\DeleteItemBrandServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\GetItemBrandServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\ListItemBrandsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\UpdateItemBrandServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\CreateItemCategoryServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\DeleteItemCategoryServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\GetItemCategoryServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\ListItemCategoriesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\UpdateItemCategoryServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemIdentifiers\CreateItemIdentifierServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemIdentifiers\DeleteItemIdentifierServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemIdentifiers\GetItemIdentifierServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemIdentifiers\ListItemIdentifiersServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemIdentifiers\UpdateItemIdentifierServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemTypes\ListItemTypesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\CreateItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\DeleteItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\GetItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\GetItemSetupSummaryServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\ListItemsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\Items\UpdateItemServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributes\CreateItemVariantAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributes\DeleteItemVariantAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributes\GetItemVariantAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributes\ListItemVariantAttributesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributes\UpdateItemVariantAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\CreateItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\DeleteItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\GetItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\ListItemVariantAttributeValuesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\UpdateItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\CreateItemVariantServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\DeleteItemVariantServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\GetItemVariantServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\ListItemVariantsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\UpdateItemVariantServiceInterface;
use Modules\Item\Application\Repositories\ComboItemRepositoryInterface;
use Modules\Item\Application\Repositories\ItemAttributeGroupRepositoryInterface;
use Modules\Item\Application\Repositories\ItemAttributeRepositoryInterface;
use Modules\Item\Application\Repositories\ItemAttributeValueRepositoryInterface;
use Modules\Item\Application\Repositories\ItemBrandRepositoryInterface;
use Modules\Item\Application\Repositories\ItemCategoryRepositoryInterface;
use Modules\Item\Application\Repositories\ItemIdentifierRepositoryInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Application\Repositories\ItemTypeRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantAttributeRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantAttributeValueRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantRepositoryInterface;
use Modules\Item\Application\UseCases\ComboItems\CreateComboItemService;
use Modules\Item\Application\UseCases\ComboItems\DeleteComboItemService;
use Modules\Item\Application\UseCases\ComboItems\GetComboItemService;
use Modules\Item\Application\UseCases\ComboItems\ListComboItemsService;
use Modules\Item\Application\UseCases\ComboItems\UpdateComboItemService;
use Modules\Item\Application\UseCases\ItemAttributeGroups\CreateItemAttributeGroupService;
use Modules\Item\Application\UseCases\ItemAttributeGroups\DeleteItemAttributeGroupService;
use Modules\Item\Application\UseCases\ItemAttributeGroups\GetItemAttributeGroupService;
use Modules\Item\Application\UseCases\ItemAttributeGroups\ListItemAttributeGroupsService;
use Modules\Item\Application\UseCases\ItemAttributeGroups\UpdateItemAttributeGroupService;
use Modules\Item\Application\UseCases\ItemAttributes\CreateItemAttributeService;
use Modules\Item\Application\UseCases\ItemAttributes\DeleteItemAttributeService;
use Modules\Item\Application\UseCases\ItemAttributes\GetItemAttributeService;
use Modules\Item\Application\UseCases\ItemAttributes\ListItemAttributesService;
use Modules\Item\Application\UseCases\ItemAttributes\UpdateItemAttributeService;
use Modules\Item\Application\UseCases\ItemAttributeValues\CreateItemAttributeValueService;
use Modules\Item\Application\UseCases\ItemAttributeValues\DeleteItemAttributeValueService;
use Modules\Item\Application\UseCases\ItemAttributeValues\GetItemAttributeValueService;
use Modules\Item\Application\UseCases\ItemAttributeValues\ListItemAttributeValuesService;
use Modules\Item\Application\UseCases\ItemAttributeValues\UpdateItemAttributeValueService;
use Modules\Item\Application\UseCases\ItemBrands\CreateItemBrandService;
use Modules\Item\Application\UseCases\ItemBrands\DeleteItemBrandService;
use Modules\Item\Application\UseCases\ItemBrands\GetItemBrandService;
use Modules\Item\Application\UseCases\ItemBrands\ListItemBrandsService;
use Modules\Item\Application\UseCases\ItemBrands\UpdateItemBrandService;
use Modules\Item\Application\UseCases\ItemCategories\CreateItemCategoryService;
use Modules\Item\Application\UseCases\ItemCategories\DeleteItemCategoryService;
use Modules\Item\Application\UseCases\ItemCategories\GetItemCategoryService;
use Modules\Item\Application\UseCases\ItemCategories\ListItemCategoriesService;
use Modules\Item\Application\UseCases\ItemCategories\UpdateItemCategoryService;
use Modules\Item\Application\UseCases\ItemIdentifiers\CreateItemIdentifierService;
use Modules\Item\Application\UseCases\ItemIdentifiers\DeleteItemIdentifierService;
use Modules\Item\Application\UseCases\ItemIdentifiers\GetItemIdentifierService;
use Modules\Item\Application\UseCases\ItemIdentifiers\ListItemIdentifiersService;
use Modules\Item\Application\UseCases\ItemIdentifiers\UpdateItemIdentifierService;
use Modules\Item\Application\UseCases\ItemTypes\ListItemTypesService;
use Modules\Item\Application\UseCases\Items\CreateItemService;
use Modules\Item\Application\UseCases\Items\DeleteItemService;
use Modules\Item\Application\UseCases\Items\GetItemService;
use Modules\Item\Application\UseCases\Items\GetItemSetupSummaryService;
use Modules\Item\Application\UseCases\Items\ListItemsService;
use Modules\Item\Application\UseCases\Items\UpdateItemService;
use Modules\Item\Application\UseCases\ItemVariantAttributes\CreateItemVariantAttributeService;
use Modules\Item\Application\UseCases\ItemVariantAttributes\DeleteItemVariantAttributeService;
use Modules\Item\Application\UseCases\ItemVariantAttributes\GetItemVariantAttributeService;
use Modules\Item\Application\UseCases\ItemVariantAttributes\ListItemVariantAttributesService;
use Modules\Item\Application\UseCases\ItemVariantAttributes\UpdateItemVariantAttributeService;
use Modules\Item\Application\UseCases\ItemVariantAttributeValues\CreateItemVariantAttributeValueService;
use Modules\Item\Application\UseCases\ItemVariantAttributeValues\DeleteItemVariantAttributeValueService;
use Modules\Item\Application\UseCases\ItemVariantAttributeValues\GetItemVariantAttributeValueService;
use Modules\Item\Application\UseCases\ItemVariantAttributeValues\ListItemVariantAttributeValuesService;
use Modules\Item\Application\UseCases\ItemVariantAttributeValues\UpdateItemVariantAttributeValueService;
use Modules\Item\Application\UseCases\ItemVariants\CreateItemVariantService;
use Modules\Item\Application\UseCases\ItemVariants\DeleteItemVariantService;
use Modules\Item\Application\UseCases\ItemVariants\GetItemVariantService;
use Modules\Item\Application\UseCases\ItemVariants\ListItemVariantsService;
use Modules\Item\Application\UseCases\ItemVariants\UpdateItemVariantService;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ComboItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeGroupModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeValueModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemBrandModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemCategoryModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemIdentifierModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemTypeModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeValueModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentComboItemRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemAttributeGroupRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemAttributeRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemAttributeValueRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemBrandRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemCategoryRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemIdentifierRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemTypeRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemVariantAttributeRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemVariantAttributeValueRepository;
use Modules\Item\Infrastructure\Persistence\Eloquent\Repositories\EloquentItemVariantRepository;

final class ItemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/item.php', 'item');

        foreach (
            [
                ListItemCategoriesServiceInterface::class => ListItemCategoriesService::class,
                GetItemCategoryServiceInterface::class => GetItemCategoryService::class,
                CreateItemCategoryServiceInterface::class => CreateItemCategoryService::class,
                UpdateItemCategoryServiceInterface::class => UpdateItemCategoryService::class,
                DeleteItemCategoryServiceInterface::class => DeleteItemCategoryService::class,
                ListItemBrandsServiceInterface::class => ListItemBrandsService::class,
                GetItemBrandServiceInterface::class => GetItemBrandService::class,
                CreateItemBrandServiceInterface::class => CreateItemBrandService::class,
                UpdateItemBrandServiceInterface::class => UpdateItemBrandService::class,
                DeleteItemBrandServiceInterface::class => DeleteItemBrandService::class,
                ListItemsServiceInterface::class => ListItemsService::class,
                GetItemServiceInterface::class => GetItemService::class,
                GetItemSetupSummaryServiceInterface::class => GetItemSetupSummaryService::class,
                CreateItemServiceInterface::class => CreateItemService::class,
                UpdateItemServiceInterface::class => UpdateItemService::class,
                DeleteItemServiceInterface::class => DeleteItemService::class,
                ListItemAttributeGroupsServiceInterface::class => ListItemAttributeGroupsService::class,
                GetItemAttributeGroupServiceInterface::class => GetItemAttributeGroupService::class,
                CreateItemAttributeGroupServiceInterface::class => CreateItemAttributeGroupService::class,
                UpdateItemAttributeGroupServiceInterface::class => UpdateItemAttributeGroupService::class,
                DeleteItemAttributeGroupServiceInterface::class => DeleteItemAttributeGroupService::class,
                ListItemAttributesServiceInterface::class => ListItemAttributesService::class,
                GetItemAttributeServiceInterface::class => GetItemAttributeService::class,
                CreateItemAttributeServiceInterface::class => CreateItemAttributeService::class,
                UpdateItemAttributeServiceInterface::class => UpdateItemAttributeService::class,
                DeleteItemAttributeServiceInterface::class => DeleteItemAttributeService::class,
                ListItemAttributeValuesServiceInterface::class => ListItemAttributeValuesService::class,
                GetItemAttributeValueServiceInterface::class => GetItemAttributeValueService::class,
                CreateItemAttributeValueServiceInterface::class => CreateItemAttributeValueService::class,
                UpdateItemAttributeValueServiceInterface::class => UpdateItemAttributeValueService::class,
                DeleteItemAttributeValueServiceInterface::class => DeleteItemAttributeValueService::class,
                ListItemVariantsServiceInterface::class => ListItemVariantsService::class,
                GetItemVariantServiceInterface::class => GetItemVariantService::class,
                CreateItemVariantServiceInterface::class => CreateItemVariantService::class,
                UpdateItemVariantServiceInterface::class => UpdateItemVariantService::class,
                DeleteItemVariantServiceInterface::class => DeleteItemVariantService::class,
                ListItemVariantAttributesServiceInterface::class => ListItemVariantAttributesService::class,
                GetItemVariantAttributeServiceInterface::class => GetItemVariantAttributeService::class,
                CreateItemVariantAttributeServiceInterface::class => CreateItemVariantAttributeService::class,
                UpdateItemVariantAttributeServiceInterface::class => UpdateItemVariantAttributeService::class,
                DeleteItemVariantAttributeServiceInterface::class => DeleteItemVariantAttributeService::class,
                ListItemVariantAttributeValuesServiceInterface::class => ListItemVariantAttributeValuesService::class,
                GetItemVariantAttributeValueServiceInterface::class => GetItemVariantAttributeValueService::class,
                CreateItemVariantAttributeValueServiceInterface::class => CreateItemVariantAttributeValueService::class,
                UpdateItemVariantAttributeValueServiceInterface::class => UpdateItemVariantAttributeValueService::class,
                DeleteItemVariantAttributeValueServiceInterface::class => DeleteItemVariantAttributeValueService::class,
                ListComboItemsServiceInterface::class => ListComboItemsService::class,
                GetComboItemServiceInterface::class => GetComboItemService::class,
                CreateComboItemServiceInterface::class => CreateComboItemService::class,
                UpdateComboItemServiceInterface::class => UpdateComboItemService::class,
                DeleteComboItemServiceInterface::class => DeleteComboItemService::class,
                ListItemIdentifiersServiceInterface::class => ListItemIdentifiersService::class,
                GetItemIdentifierServiceInterface::class => GetItemIdentifierService::class,
                CreateItemIdentifierServiceInterface::class => CreateItemIdentifierService::class,
                UpdateItemIdentifierServiceInterface::class => UpdateItemIdentifierService::class,
                DeleteItemIdentifierServiceInterface::class => DeleteItemIdentifierService::class,
                ListItemTypesServiceInterface::class => ListItemTypesService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(ItemCategoryRepositoryInterface::class, function (): ItemCategoryRepositoryInterface {
            return new EloquentItemCategoryRepository(new ItemCategoryModel());
        });
        $this->app->singleton(ItemBrandRepositoryInterface::class, function (): ItemBrandRepositoryInterface {
            return new EloquentItemBrandRepository(new ItemBrandModel());
        });
        $this->app->singleton(ItemRepositoryInterface::class, function (): ItemRepositoryInterface {
            return new EloquentItemRepository(new ItemModel());
        });
        $this->app->singleton(ItemAttributeGroupRepositoryInterface::class, function (): ItemAttributeGroupRepositoryInterface {
            return new EloquentItemAttributeGroupRepository(new ItemAttributeGroupModel());
        });
        $this->app->singleton(ItemAttributeRepositoryInterface::class, function (): ItemAttributeRepositoryInterface {
            return new EloquentItemAttributeRepository(new ItemAttributeModel());
        });
        $this->app->singleton(ItemAttributeValueRepositoryInterface::class, function (): ItemAttributeValueRepositoryInterface {
            return new EloquentItemAttributeValueRepository(new ItemAttributeValueModel());
        });
        $this->app->singleton(ItemVariantRepositoryInterface::class, function (): ItemVariantRepositoryInterface {
            return new EloquentItemVariantRepository(new ItemVariantModel());
        });
        $this->app->singleton(ItemVariantAttributeRepositoryInterface::class, function (): ItemVariantAttributeRepositoryInterface {
            return new EloquentItemVariantAttributeRepository(new ItemVariantAttributeModel());
        });
        $this->app->singleton(ItemVariantAttributeValueRepositoryInterface::class, function (): ItemVariantAttributeValueRepositoryInterface {
            return new EloquentItemVariantAttributeValueRepository(new ItemVariantAttributeValueModel());
        });
        $this->app->singleton(ComboItemRepositoryInterface::class, function (): ComboItemRepositoryInterface {
            return new EloquentComboItemRepository(new ComboItemModel());
        });
        $this->app->singleton(ItemIdentifierRepositoryInterface::class, function (): ItemIdentifierRepositoryInterface {
            return new EloquentItemIdentifierRepository(new ItemIdentifierModel());
        });
        $this->app->singleton(ItemTypeRepositoryInterface::class, function (): ItemTypeRepositoryInterface {
            return new EloquentItemTypeRepository(new ItemTypeModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
