<?php

declare(strict_types=1);

return [
    'billing_timezone' => env(
        'VEHICLE_RENTAL_BILLING_TIMEZONE',
        env('APP_TIMEZONE', 'UTC'),
    ),
    'defaults' => [],
    'legal_contexts' => [
        'company',
        'personal',
    ],
    'reservation_sources' => [
        'walk_in',
    ],
    'finance_interest_methods' => [
        'flat',
        'reducing_balance',
    ],
    'finance_installment_frequencies' => [
        'weekly',
        'monthly',
        'quarterly',
        'yearly',
    ],
    'public_custody_event_types' => [
        \Modules\VehicleRental\Enums\RentalCustodyEventType::OwnerToCompany->value,
        \Modules\VehicleRental\Enums\RentalCustodyEventType::CompanyToCustomer->value,
        \Modules\VehicleRental\Enums\RentalCustodyEventType::CustomerToCompany->value,
        \Modules\VehicleRental\Enums\RentalCustodyEventType::CompanyToOwner->value,
        \Modules\VehicleRental\Enums\RentalCustodyEventType::InternalTransfer->value,
    ],
];
