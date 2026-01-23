<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers;

use App\Core\Exceptions\ServiceException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Resources\AppointmentResource;
use Modules\Appointment\Services\AppointmentOrchestrator;

/**
 * Appointment Orchestration Controller
 *
 * Handles complex appointment booking with customer/vehicle validation
 * Demonstrates multi-step orchestration with automatic rollback
 */
class AppointmentOrchestrationController extends Controller
{
    public function __construct(
        private readonly AppointmentOrchestrator $orchestrator
    ) {}

    /**
     * Book appointment with full validation and orchestration
     *
     * This endpoint handles:
     * - Customer lookup/creation (by email or phone)
     * - Vehicle lookup/registration (by license plate)
     * - Bay availability validation
     * - Appointment creation
     * - Bay slot reservation
     * - Confirmation notifications (async)
     *
     * Supports both existing and new customers/vehicles.
     *
     * @OA\Post(
     *     path="/api/v1/appointments/book",
     *     tags={"Appointments"},
     *     summary="Book an appointment with full orchestration",
     *     description="Creates appointment, handles customer/vehicle registration, and validates availability",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"scheduled_date", "scheduled_time", "branch_id"},
     *
     *             @OA\Property(property="customer_id", type="integer", description="Existing customer ID"),
     *             @OA\Property(property="customer_email", type="string", format="email", description="Customer email (for new/lookup)"),
     *             @OA\Property(property="customer_phone", type="string", description="Customer phone (for new/lookup)"),
     *             @OA\Property(property="customer_name", type="string", description="Customer name (for new)"),
     *             @OA\Property(property="vehicle_id", type="integer", description="Existing vehicle ID"),
     *             @OA\Property(property="license_plate", type="string", description="License plate (for new/lookup)"),
     *             @OA\Property(property="vehicle_make", type="string"),
     *             @OA\Property(property="vehicle_model", type="string"),
     *             @OA\Property(property="vehicle_year", type="integer"),
     *             @OA\Property(property="branch_id", type="integer", description="Service branch"),
     *             @OA\Property(property="bay_id", type="integer", description="Specific bay (optional)"),
     *             @OA\Property(property="scheduled_date", type="string", format="date", example="2024-02-15"),
     *             @OA\Property(property="scheduled_time", type="string", format="time", example="09:00"),
     *             @OA\Property(property="service_type", type="string", example="oil_change"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="estimated_duration", type="integer", description="Minutes", example=60)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Appointment booked successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="appointment", ref="#/components/schemas/Appointment"),
     *                 @OA\Property(property="customer", type="object"),
     *                 @OA\Property(property="vehicle", type="object"),
     *                 @OA\Property(property="is_new_customer", type="boolean"),
     *                 @OA\Property(property="is_new_vehicle", type="boolean")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=400, description="Validation error or availability conflict"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function book(Request $request): JsonResponse
    {
        try {
            // Validate request
            $data = $request->validate([
                // Customer identification (one of these is required)
                'customer_id' => 'sometimes|integer|exists:customers,id',
                'customer_email' => 'required_without_all:customer_id,customer_phone|email',
                'customer_phone' => 'required_without_all:customer_id,customer_email|string',
                'customer_name' => 'required_without:customer_id|string|max:255',

                // Vehicle identification (one of these is required)
                'vehicle_id' => 'sometimes|integer|exists:vehicles,id',
                'license_plate' => 'required_without:vehicle_id|string|max:20',
                'vehicle_make' => 'sometimes|string|max:100',
                'vehicle_model' => 'sometimes|string|max:100',
                'vehicle_year' => 'sometimes|integer|min:1900|max:'.(date('Y') + 1),
                'vin' => 'sometimes|string|max:17',
                'vehicle_color' => 'sometimes|string|max:50',

                // Appointment details
                'branch_id' => 'required|integer|exists:branches,id',
                'bay_id' => 'sometimes|integer|exists:bays,id',
                'scheduled_date' => 'required|date|after_or_equal:today',
                'scheduled_time' => 'required|date_format:H:i',
                'service_type' => 'sometimes|string|in:oil_change,inspection,repair,maintenance,diagnostic',
                'description' => 'sometimes|string|max:1000',
                'estimated_duration' => 'sometimes|integer|min:15|max:480',
                'priority' => 'sometimes|string|in:low,normal,high,urgent',
            ]);

            // Execute orchestrated operation
            $result = $this->orchestrator->bookAppointmentWithFullValidation($data);

            return $this->createdResponse([
                'appointment' => new AppointmentResource($result['appointment']),
                'customer' => new \Modules\Customer\Resources\CustomerResource($result['customer']),
                'vehicle' => new \Modules\Customer\Resources\VehicleResource($result['vehicle']),
                'is_new_customer' => $result['isNewCustomer'],
                'is_new_vehicle' => $result['isNewVehicle'],
                'message' => $this->buildSuccessMessage($result),
            ]);
        } catch (ServiceException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            \Log::error('Failed to book appointment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('An unexpected error occurred while booking the appointment.', 500);
        }
    }

    /**
     * Confirm an appointment
     *
     * @OA\Post(
     *     path="/api/v1/appointments/{id}/confirm",
     *     tags={"Appointments"},
     *     summary="Confirm an appointment",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Appointment confirmed")
     * )
     */
    public function confirm(int $id): JsonResponse
    {
        try {
            $appointment = $this->orchestrator->confirmAppointment($id);

            return $this->successResponse(
                new AppointmentResource($appointment),
                'Appointment confirmed successfully. Confirmation sent to customer.'
            );
        } catch (ServiceException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            \Log::error('Failed to confirm appointment', [
                'appointment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('An unexpected error occurred.', 500);
        }
    }

    /**
     * Build success message based on result
     */
    private function buildSuccessMessage(array $result): string
    {
        $messages = ['Appointment booked successfully.'];

        if ($result['isNewCustomer']) {
            $messages[] = 'Welcome! Your customer profile has been created.';
        }

        if ($result['isNewVehicle']) {
            $messages[] = 'Vehicle registered successfully.';
        }

        $messages[] = 'Confirmation notification sent.';

        return implode(' ', $messages);
    }
}
