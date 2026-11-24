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

enum BiometricProtocol: string
{
    case ZkTecoPush = 'zkteco_push';
    case ZkTecoSocket = 'zkteco_socket';
    case HikvisionIsapi = 'hikvision_isapi';
    case DahuaCgi = 'dahua_cgi';
    case GenericWebhook = 'generic_webhook';
    case UsbImport = 'usb_import';

    public function label(): string
    {
        return match ($this) {
            self::ZkTecoPush => 'ZKTeco ADMS / iClock Push (HTTP/HTTPS)',
            self::ZkTecoSocket => 'ZKTeco Standalone (TCP Socket 4370)',
            self::HikvisionIsapi => 'Hikvision ISAPI (HTTP/REST)',
            self::DahuaCgi => 'Dahua CGI RPC (HTTP)',
            self::GenericWebhook => 'Generic IoT Cloud Webhook (JSON)',
            self::UsbImport => 'USB Flash / File Log Import',
        };
    }
}
