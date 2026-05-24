<?php

declare(strict_types=1);

use Modules\Pricing\Application\Repositories\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\SupplierPriceListRepositoryInterface;

return [
    'precision' => [
        'scale' => 4,
    ],

    'defaults' => [
        'price_list_type' => 'sales',
        'discount_type' => 'percentage',
        'minimum_quantity' => 1,
        'is_active' => true,
        'is_default' => false,
        'priority' => 0,
    ],

    'price_list_types' => [
        'sales',
        'purchase',
    ],

    'discount_types' => [
        'percentage',
        'fixed',
    ],

    'price_dimensions' => [
        'variant_id',
        'warehouse_id',
        'warehouse_location_id',
        'batch_id',
        'serial_id',
    ],

    'immutable' => [
        'price_lists' => [],
        'price_list_items' => [],
        'supplier_price_lists' => [],
        'customer_price_lists' => [],
    ],

    'resources' => [
        'price_lists' => [
            'label' => 'Price list',
            'repository' => PriceListRepositoryInterface::class,
            'rules' => [
                'organization_unit_id' => ['nullable', 'integer'],
                'name' => ['required', 'string', 'max:255'],
                'type' => ['nullable', 'string', 'max:50'],
                'currency_id' => ['nullable', 'integer'],
                'is_default' => ['sometimes', 'boolean'],
                'valid_from' => ['nullable', 'date'],
                'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
                'is_active' => ['sometimes', 'boolean'],
                'metadata' => ['nullable', 'array'],
                'row_version' => ['nullable', 'integer', 'min:1'],
            ],
        ],

        'price_list_items' => [
            'label' => 'Price list item',
            'repository' => PriceListItemRepositoryInterface::class,
            'rules' => [
                'organization_unit_id' => ['nullable', 'integer'],
                'price_list_id' => ['required', 'integer'],
                'item_id' => ['required', 'integer'],
                'variant_id' => ['nullable', 'integer'],
                'warehouse_id' => ['nullable', 'integer'],
                'warehouse_location_id' => ['nullable', 'integer'],
                'batch_id' => ['nullable', 'integer'],
                'serial_id' => ['nullable', 'integer'],
                'uom_id' => ['required', 'integer'],
                'min_quantity' => ['nullable', 'numeric', 'min:0'],
                'price' => ['required', 'numeric', 'min:0'],
                'discount_type' => ['nullable', 'string', 'max:50'],
                'discount_value' => ['nullable', 'numeric', 'min:0'],
                'valid_from' => ['nullable', 'date'],
                'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
                'metadata' => ['nullable', 'array'],
                'row_version' => ['nullable', 'integer', 'min:1'],
            ],
        ],

        'supplier_price_lists' => [
            'label' => 'Supplier price list',
            'repository' => SupplierPriceListRepositoryInterface::class,
            'rules' => [
                'organization_unit_id' => ['nullable', 'integer'],
                'supplier_id' => ['required', 'integer'],
                'price_list_id' => ['required', 'integer'],
                'priority' => ['nullable', 'integer', 'min:0'],
                'metadata' => ['nullable', 'array'],
                'row_version' => ['nullable', 'integer', 'min:1'],
            ],
        ],

        'customer_price_lists' => [
            'label' => 'Customer price list',
            'repository' => CustomerPriceListRepositoryInterface::class,
            'rules' => [
                'organization_unit_id' => ['nullable', 'integer'],
                'customer_id' => ['required', 'integer'],
                'price_list_id' => ['required', 'integer'],
                'priority' => ['nullable', 'integer', 'min:0'],
                'metadata' => ['nullable', 'array'],
                'row_version' => ['nullable', 'integer', 'min:1'],
            ],
        ],
    ],
];
