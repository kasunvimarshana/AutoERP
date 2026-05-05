<?php

declare(strict_types=1);

namespace Modules\Notifications\Domain\ValueObjects;

enum NotificationType: string
{
    case RentalOverdue       = 'rental_overdue';
    case ServiceDue          = 'service_due';
    case DocumentExpiry      = 'document_expiry';
    case MaintenanceReminder = 'maintenance_reminder';
    case PaymentDue          = 'payment_due';
    case Other               = 'other';
}
