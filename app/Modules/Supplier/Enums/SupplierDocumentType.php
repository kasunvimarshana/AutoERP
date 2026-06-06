<?php

declare(strict_types=1);

namespace Modules\Supplier\Enums;

enum SupplierDocumentType: string
{
    case BusinessRegistrationCertificate = 'br_certificate';
    case TaxCertificate = 'tax_certificate';
    case VatCertificate = 'vat_certificate';
    case SvatCertificate = 'svat_certificate';
    case Contract = 'contract';
    case License = 'license';
    case Other = 'other';
}
