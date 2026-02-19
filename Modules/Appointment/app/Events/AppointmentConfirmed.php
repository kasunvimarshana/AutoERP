<?php

declare(strict_types=1);

namespace Modules\Appointment\Events;

use App\Core\Events\BaseDomainEvent;
use Modules\Appointment\Models\Appointment;

/**
 * Appointment Confirmed Event
 *
 * Dispatched when an appointment is confirmed.
 * Triggers:
 * - Confirmation notification to customer
 * - Reminder scheduling
 * - Technician notification
 * - Bay preparation
 */
class AppointmentConfirmed extends BaseDomainEvent
{
    /**
     * Create a new event instance
     */
    public function __construct(
        public readonly Appointment $appointment
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getEventPayload(): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'customer_id' => $this->appointment->customer_id,
            'scheduled_date' => $this->appointment->scheduled_date,
            'scheduled_time' => $this->appointment->scheduled_time,
        ];
    }
}
