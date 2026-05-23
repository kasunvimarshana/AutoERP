<?php

declare(strict_types=1);

use Modules\Supplier\Application\Repositories\SupplierAddressRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierContactRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierItemRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierVehicleRepositoryInterface;

return [
    'precision' => [
        'scale' => 4,
    ],

    'supplier_types' => [
        'individual',
        'company',
    ],

    'supplier_statuses' => [
        'active',
        'inactive',
        'blocked',
    ],

    'address_types' => [
        'billing',
        'shipping',
        'office',
        'other',
    ],

    'defaults' => [
        'supplier_type' => 'individual',
        'supplier_status' => 'active',
        'address_type' => 'billing',
        'payment_terms_days' => 30,
        'minimum_order_quantity' => 1,
    ],

    'immutable' => [
        'suppliers' => [
            'status_column' => 'status',
            'statuses' => ['blocked'],
        ],
    ],

    'resources' => [
        'suppliers' => [
            'repository' => SupplierRepositoryInterface::class,
            'label' => 'Supplier',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'metadata' => ['nullable', 'array'],
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
                'code' => ['nullable', 'string', 'max:255'],
                'registration_number' => ['required', 'string', 'max:255'],
                'type' => ['nullable', 'string'],
                'tax_number' => ['nullable', 'string', 'max:255'],
                'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
                'credit_limit' => ['nullable', 'numeric', 'min:0'],
                'payment_terms_days' => ['nullable', 'integer', 'min:0'],
                'status' => ['nullable', 'string'],
                'notes' => ['nullable', 'string'],
                'created_by' => ['nullable', 'integer'],
                'updated_by' => ['nullable', 'integer'],
                'ap_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            ],
        ],

        'supplier_contacts' => [
            'repository' => SupplierContactRepositoryInterface::class,
            'label' => 'Supplier contact',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'metadata' => ['nullable', 'array'],
                'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
                'name' => ['required', 'string', 'max:255'],
                'role' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'is_primary' => ['nullable', 'boolean'],
            ],
        ],

        'supplier_addresses' => [
            'repository' => SupplierAddressRepositoryInterface::class,
            'label' => 'Supplier address',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'metadata' => ['nullable', 'array'],
                'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
                'type' => ['nullable', 'string'],
                'label' => ['nullable', 'string', 'max:255'],
                'address_line1' => ['required', 'string', 'max:255'],
                'address_line2' => ['nullable', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'state' => ['nullable', 'string', 'max:255'],
                'postal_code' => ['required', 'string', 'max:255'],
                'country_id' => ['required', 'integer', 'exists:countries,id'],
                'is_default' => ['nullable', 'boolean'],
                'geo_lat' => ['nullable', 'numeric'],
                'geo_lng' => ['nullable', 'numeric'],
            ],
        ],

        'supplier_vehicles' => [
            'repository' => SupplierVehicleRepositoryInterface::class,
            'label' => 'Supplier vehicle',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'metadata' => ['nullable', 'array'],
                'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
                'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
                'is_current' => ['nullable', 'boolean'],
                'is_active' => ['nullable', 'boolean'],
            ],
        ],

        'supplier_items' => [
            'repository' => SupplierItemRepositoryInterface::class,
            'label' => 'Supplier item',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'metadata' => ['nullable', 'array'],
                'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
                'item_id' => ['required', 'integer', 'exists:items,id'],
                'variant_id' => ['nullable', 'integer', 'exists:item_variants,id'],
                'supplier_sku' => ['nullable', 'string', 'max:255'],
                'lead_time_days' => ['nullable', 'integer', 'min:0'],
                'min_order_qty' => ['nullable', 'numeric', 'min:0'],
                'is_preferred' => ['nullable', 'boolean'],
                'last_purchase_price' => ['nullable', 'numeric', 'min:0'],
            ],
        ],
    ],
];
