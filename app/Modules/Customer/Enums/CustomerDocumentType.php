<?php

declare(strict_types=1);

namespace Modules\Customer\Enums;

enum CustomerDocumentType: string
{
    case BusinessRegistration = 'business_registration';
    case TaxCertificate = 'tax_certificate';
    case VatCertificate = 'vat_certificate';
    case SvatCertificate = 'svat_certificate';
    case Contract = 'contract';
    case License = 'license';
    case Insurance = 'insurance';
    case IdDocument = 'id_document';
    case Other = 'other';
}
