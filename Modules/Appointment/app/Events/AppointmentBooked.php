<?php

declare(strict_types=1);

namespace Modules\Appointment\Events;

use App\Core\Events\BaseDomainEvent;
use Modules\Appointment\Models\Appointment;

/**
 * Appointment Booked Event
 *
 * Dispatched when a new appointment is successfully booked.
 * Triggers:
 * - Confirmation email/SMS to customer
 * - Welcome email for new customers
 * - Vehicle registration confirmation for new vehicles
 * - Calendar updates
 * - Bay reservation notifications
 */
class AppointmentBooked extends BaseDomainEvent
{
    /**
     * Create a new event instance
     */
    public function __construct(
        public readonly Appointment $appointment,
        public readonly bool $isNewCustomer = false,
        public readonly bool $isNewVehicle = false
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
            'vehicle_id' => $this->appointment->vehicle_id,
            'branch_id' => $this->appointment->branch_id,
            'scheduled_date' => $this->appointment->scheduled_date,
            'scheduled_time' => $this->appointment->scheduled_time,
            'is_new_customer' => $this->isNewCustomer,
            'is_new_vehicle' => $this->isNewVehicle,
        ];
    }
}
