<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCustodyEventType: string
{
    case OwnerToCompany = 'owner_to_company';
    case CompanyToCustomer = 'company_to_customer';
    case CustomerToCompany = 'customer_to_company';
    case CompanyToOwner = 'company_to_owner';
    case ReplacementOut = 'replacement_out';
    case ReplacementIn = 'replacement_in';
    case InternalTransfer = 'internal_transfer';
}
