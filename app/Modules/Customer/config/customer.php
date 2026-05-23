<?php

declare(strict_types=1);

use Modules\Customer\Application\Repositories\CustomerAddressRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerContactRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerVehicleRepositoryInterface;

return [
    'precision' => [
        'scale' => 4,
    ],

    'customer_types' => [
        'individual',
        'company',
    ],

    'customer_statuses' => [
        'ACTIVE',
        'INACTIVE',
        'BLOCKED',
    ],

    'address_types' => [
        'billing',
        'shipping',
        'office',
        'other',
    ],

    'defaults' => [
        'customer_type' => 'individual',
        'customer_status' => 'ACTIVE',
        'address_type' => 'billing',
        'payment_terms_days' => 30,
    ],

    'immutable' => [
        'customers' => [
            'status_column' => 'status',
            'statuses' => ['BLOCKED'],
        ],
    ],

    'resources' => [
        'customers' => [
            'repository' => CustomerRepositoryInterface::class,
            'label' => 'Customer',
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
                'ar_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            ],
        ],

        'customer_contacts' => [
            'repository' => CustomerContactRepositoryInterface::class,
            'label' => 'Customer contact',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'metadata' => ['nullable', 'array'],
                'customer_id' => ['required', 'integer', 'exists:customers,id'],
                'name' => ['required', 'string', 'max:255'],
                'role' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'is_primary' => ['nullable', 'boolean'],
            ],
        ],

        'customer_addresses' => [
            'repository' => CustomerAddressRepositoryInterface::class,
            'label' => 'Customer address',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'metadata' => ['nullable', 'array'],
                'customer_id' => ['required', 'integer', 'exists:customers,id'],
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

        'customer_vehicles' => [
            'repository' => CustomerVehicleRepositoryInterface::class,
            'label' => 'Customer vehicle',
            'rules' => [
                'row_version' => ['nullable', 'integer', 'min:1'],
                'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
                'metadata' => ['nullable', 'array'],
                'customer_id' => ['required', 'integer', 'exists:customers,id'],
                'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
                'is_current' => ['nullable', 'boolean'],
                'is_active' => ['nullable', 'boolean'],
            ],
        ],
    ],
];
