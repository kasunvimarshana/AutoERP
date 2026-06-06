<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleDocumentType: string
{
    case Registration = 'registration';
    case Insurance = 'insurance';
    case EmissionTest = 'emission_test';
    case RevenueLicense = 'revenue_license';
    case FitnessCertificate = 'fitness_certificate';
    case LeaseDocument = 'lease_document';
    case OwnershipDocument = 'ownership_document';
    case Warranty = 'warranty';
    case Other = 'other';
}
