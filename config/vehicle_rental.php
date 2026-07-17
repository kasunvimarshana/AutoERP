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
    'finance_interest_methods' => array_map(
        static fn (\Modules\VehicleRental\Enums\VehicleFinanceInterestMethod $method): string => $method->value,
        \Modules\VehicleRental\Enums\VehicleFinanceInterestMethod::cases(),
    ),
    'finance_installment_frequencies' => array_map(
        static fn (\Modules\VehicleRental\Enums\VehicleFinanceInstallmentFrequency $frequency): string => $frequency->value,
        \Modules\VehicleRental\Enums\VehicleFinanceInstallmentFrequency::cases(),
    ),
    'public_custody_event_types' => [
        \Modules\VehicleRental\Enums\RentalCustodyEventType::OwnerToCompany->value,
        \Modules\VehicleRental\Enums\RentalCustodyEventType::CompanyToCustomer->value,
        \Modules\VehicleRental\Enums\RentalCustodyEventType::CustomerToCompany->value,
        \Modules\VehicleRental\Enums\RentalCustodyEventType::CompanyToOwner->value,
        \Modules\VehicleRental\Enums\RentalCustodyEventType::InternalTransfer->value,
    ],
];
