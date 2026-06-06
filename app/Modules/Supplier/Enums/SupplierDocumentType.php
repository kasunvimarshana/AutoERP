<?php

declare(strict_types=1);

namespace Modules\Supplier\Enums;

enum SupplierDocumentType: string
{
    case BusinessRegistration = 'business_registration';
    case TaxCertificate = 'tax_certificate';
    case VatCertificate = 'vat_certificate';
    case SvatCertificate = 'svat_certificate';
    case Contract = 'contract';
    case License = 'license';
    case Insurance = 'insurance';
    case Other = 'other';
}
