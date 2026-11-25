<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Enums;

enum CredentialType: string
{
    case RfidCard = 'rfid_card';
    case NfcTag = 'nfc_tag';
    case Barcode = 'barcode';
    case MobileDeviceUuid = 'mobile_device_uuid';
}
