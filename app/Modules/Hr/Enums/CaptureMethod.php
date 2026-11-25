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

enum CaptureMethod: string
{
    case Biometric = 'biometric';
    case RfidCard = 'rfid_card';
    case NfcTag = 'nfc_tag';
    case Barcode = 'barcode';
    case TotpQr = 'totp_qr';
    case MobileGps = 'mobile_gps';
    case WebPortal = 'web_portal';
    case ManualAdmin = 'manual_admin';
    case FileUpload = 'file_upload';
}
