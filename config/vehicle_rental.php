<?php

declare(strict_types=1);

return [
    'billing_timezone' => env(
        'VEHICLE_RENTAL_BILLING_TIMEZONE',
        env('APP_TIMEZONE', 'UTC'),
    ),
    'defaults' => [
        'legal_context' => 'company',
        'rental_mode' => \Modules\VehicleRental\Enums\RentalMode::WithDriver->value,
        'billing_cycle' => \Modules\VehicleRental\Enums\RentalBillingCycle::Monthly->value,
        'billing_basis' => \Modules\VehicleRental\Enums\RentalBillingBasis::CalendarMonth->value,
        'proration_rule' => \Modules\VehicleRental\Enums\RentalProrationRule::ExactDayCount->value,
        'excess_km_method' => \Modules\VehicleRental\Enums\RentalExcessKmMethod::Period->value,
        'payment_term_days' => 30,
        'reservation_source' => 'walk_in',
        'expense_type' => \Modules\VehicleRental\Enums\RentalExpenseType::Fuel->value,
        'expense_allocation_type' => \Modules\VehicleRental\Enums\RentalExpenseAllocationType::CompanyCost->value,
        'vehicle_source_type' => \Modules\VehicleRental\Enums\RentalVehicleSourceType::CompanyOwned->value,
        'finance_interest_method' => 'flat',
        'finance_installment_frequency' => 'monthly',
        'finance_installment_count' => 12,
        'finance_payment_term_days' => 0,
    ],
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
