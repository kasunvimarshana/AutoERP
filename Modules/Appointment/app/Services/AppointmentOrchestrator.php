<?php

declare(strict_types=1);

namespace Modules\Appointment\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseOrchestrator;
use Illuminate\Support\Facades\Log;
use Modules\Appointment\Events\AppointmentBooked;
use Modules\Appointment\Events\AppointmentConfirmed;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Repositories\AppointmentRepository;
use Modules\Customer\Services\CustomerService;
use Modules\Customer\Services\VehicleService;
use Modules\Organization\Services\BranchService;

/**
 * Appointment Orchestrator Service
 *
 * Orchestrates appointment booking across multiple modules:
 * - Customer validation/creation
 * - Vehicle validation/registration
 * - Bay availability check
 * - Appointment creation
 * - Confirmation notifications
 *
 * Demonstrates:
 * - Complex validation workflows
 * - Multi-step orchestration
 * - Rollback on validation failure
 * - Event-driven notifications
 */
class AppointmentOrchestrator extends BaseOrchestrator
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AppointmentService $appointmentService,
        private readonly CustomerService $customerService,
        private readonly VehicleService $vehicleService,
        private readonly BayService $bayService,
        private readonly BranchService $branchService
    ) {
    }

    /**
     * Book appointment with full validation and orchestration
     *
     * Workflow:
     * 1. Validate or create customer
     * 2. Validate or register vehicle
     * 3. Check bay availability
     * 4. Validate branch capacity
     * 5. Create appointment
     * 6. Reserve bay slot
     * 7. Send confirmation (via events)
     *
     * @param  array<string, mixed>  $data  Appointment data including customer, vehicle, schedule info
     * @return array{appointment: Appointment, customer: mixed, vehicle: mixed, isNewCustomer: bool, isNewVehicle: bool}
     *
     * @throws ServiceException
     */
    public function bookAppointmentWithFullValidation(array $data): array
    {
        return $this->executeSteps([
            // Step 1: Validate/Create Customer
            'handle_customer' => function () use ($data) {
                $isNewCustomer = false;

                if (isset($data['customer_id'])) {
                    // Existing customer
                    $customer = $this->customerService->getById($data['customer_id']);

                    if (! $customer) {
                        throw new ServiceException('Customer not found');
                    }
                } elseif (isset($data['customer_email']) || isset($data['customer_phone'])) {
                    // Try to find existing customer by email or phone
                    $customer = null;

                    if (isset($data['customer_email'])) {
                        $customers = $this->customerService->getAll(['email' => $data['customer_email']]);
                        $customer = $customers->first();
                    }

                    if (! $customer && isset($data['customer_phone'])) {
                        $customers = $this->customerService->getAll(['phone' => $data['customer_phone']]);
                        $customer = $customers->first();
                    }

                    // Create new customer if not found
                    if (! $customer) {
                        $customer = $this->customerService->create([
                            'name' => $data['customer_name'] ?? 'Guest',
                            'email' => $data['customer_email'] ?? null,
                            'phone' => $data['customer_phone'],
                            'status' => 'active',
                        ]);
                        $isNewCustomer = true;
                    }
                } else {
                    throw new ServiceException('Customer information is required');
                }

                return ['customer' => $customer, 'isNewCustomer' => $isNewCustomer];
            },

            // Step 2: Validate/Register Vehicle
            'handle_vehicle' => function () use ($data, &$customerResult) {
                $customer = $customerResult['customer'];
                $isNewVehicle = false;

                if (isset($data['vehicle_id'])) {
                    // Existing vehicle
                    $vehicle = $this->vehicleService->getById($data['vehicle_id']);

                    if (! $vehicle) {
                        throw new ServiceException('Vehicle not found');
                    }

                    // Verify vehicle belongs to customer
                    if ($vehicle->customer_id !== $customer->id) {
                        throw new ServiceException('Vehicle does not belong to the specified customer');
                    }
                } elseif (isset($data['license_plate'])) {
                    // Try to find existing vehicle
                    $vehicle = $this->vehicleService->getAll(['license_plate' => $data['license_plate']])->first();

                    // Create new vehicle if not found
                    if (! $vehicle) {
                        $vehicle = $this->vehicleService->create([
                            'customer_id' => $customer->id,
                            'license_plate' => $data['license_plate'],
                            'make' => $data['vehicle_make'] ?? null,
                            'model' => $data['vehicle_model'] ?? null,
                            'year' => $data['vehicle_year'] ?? null,
                            'vin' => $data['vin'] ?? null,
                            'color' => $data['vehicle_color'] ?? null,
                            'status' => 'active',
                        ]);
                        $isNewVehicle = true;
                    }
                } else {
                    throw new ServiceException('Vehicle information is required');
                }

                return ['vehicle' => $vehicle, 'isNewVehicle' => $isNewVehicle];
            },

            // Step 3: Validate Branch and Bay Availability
            'validate_availability' => function () use ($data) {
                $branchId = $data['branch_id'];
                $scheduledDate = $data['scheduled_date'];
                $scheduledTime = $data['scheduled_time'];

                // Check if branch exists and is active
                $branch = $this->branchService->getById($branchId);
                if (! $branch || $branch->status !== 'active') {
                    throw new ServiceException('Branch is not available');
                }

                // Check bay availability (if bay_id provided)
                if (isset($data['bay_id'])) {
                    $isAvailable = $this->bayService->checkAvailability(
                        $data['bay_id'],
                        $scheduledDate,
                        $scheduledTime,
                        $data['estimated_duration'] ?? 60
                    );

                    if (! $isAvailable) {
                        throw new ServiceException('Selected bay is not available at the requested time');
                    }
                }

                return ['branch' => $branch];
            },

            // Step 4: Create Appointment
            'create_appointment' => function () use ($data, &$customerResult, &$vehicleResult) {
                $customer = $customerResult['customer'];
                $vehicle = $vehicleResult['vehicle'];

                $appointmentData = [
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'branch_id' => $data['branch_id'],
                    'bay_id' => $data['bay_id'] ?? null,
                    'scheduled_date' => $data['scheduled_date'],
                    'scheduled_time' => $data['scheduled_time'],
                    'service_type' => $data['service_type'] ?? 'general',
                    'description' => $data['description'] ?? '',
                    'estimated_duration' => $data['estimated_duration'] ?? 60,
                    'status' => 'scheduled',
                    'priority' => $data['priority'] ?? 'normal',
                ];

                $appointment = $this->appointmentService->create($appointmentData);

                return ['appointment' => $appointment];
            },

            // Step 5: Reserve Bay Slot (if bay specified)
            'reserve_bay' => function () use ($data, &$appointmentResult) {
                if (isset($data['bay_id'])) {
                    $appointment = $appointmentResult['appointment'];

                    $this->bayService->reserveSlot(
                        $data['bay_id'],
                        $appointment->id,
                        $appointment->scheduled_date,
                        $appointment->scheduled_time,
                        $appointment->estimated_duration
                    );

                    return ['bay_reserved' => true];
                }

                return ['bay_reserved' => false];
            },
        ], 'BookAppointmentOrchestration');

        // Extract results from executeSteps
        $customer = $customerResult['customer'];
        $vehicle = $vehicleResult['vehicle'];
        $appointment = $appointmentResult['appointment'];
        $isNewCustomer = $customerResult['isNewCustomer'];
        $isNewVehicle = $vehicleResult['isNewVehicle'];

        // Dispatch events for async operations (notifications, etc.)
        event(new AppointmentBooked($appointment, $isNewCustomer, $isNewVehicle));

        Log::info('Appointment booked successfully', [
            'appointment_id' => $appointment->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'is_new_customer' => $isNewCustomer,
            'is_new_vehicle' => $isNewVehicle,
        ]);

        return [
            'appointment' => $appointment->fresh(['customer', 'vehicle', 'bay']),
            'customer' => $customer,
            'vehicle' => $vehicle,
            'isNewCustomer' => $isNewCustomer,
            'isNewVehicle' => $isNewVehicle,
        ];
    }

    /**
     * Confirm appointment with SMS/Email notification
     *
     * @param  int  $appointmentId
     * @return Appointment
     *
     * @throws ServiceException
     */
    public function confirmAppointment(int $appointmentId): Appointment
    {
        return $this->executeInTransaction(function () use ($appointmentId) {
            $appointment = $this->appointmentService->updateStatus($appointmentId, 'confirmed');

            // Dispatch event for notifications
            event(new AppointmentConfirmed($appointment));

            return $appointment;
        }, 'ConfirmAppointment');
    }

    /**
     * Compensation for failed appointment booking
     */
    protected function compensate(): void
    {
        Log::warning('Appointment booking failed, performing compensation', [
            'completed_steps' => $this->completedSteps,
        ]);

        // Could release reserved bay slots, send failure notifications, etc.
        // In our case, database rollback handles most of the cleanup
    }
}
