<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Requests\StoreAppointmentRequest;
use Modules\Appointment\Requests\UpdateAppointmentRequest;
use Modules\Appointment\Resources\AppointmentResource;
use Modules\Appointment\Services\AppointmentService;

/**
 * Appointment Controller
 *
 * Handles HTTP requests for Appointment operations
 * Follows Controller → Service → Repository pattern
 */
class AppointmentController extends Controller
{
    /**
     * AppointmentController constructor
     */
    public function __construct(
        private readonly AppointmentService $appointmentService
    ) {}

    /**
     * Display a listing of appointments
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $appointments = $this->appointmentService->getAll($filters);

        return $this->successResponse(
            AppointmentResource::collection($appointments),
            __('appointment::messages.appointments_retrieved')
        );
    }

    /**
     * Store a newly created appointment
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointmentService->create($request->validated());

        return $this->createdResponse(
            new AppointmentResource($appointment),
            __('appointment::messages.appointment_created')
        );
    }

    /**
     * Display the specified appointment
     */
    public function show(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->getWithRelations($id);

        return $this->successResponse(
            new AppointmentResource($appointment),
            __('appointment::messages.appointment_retrieved')
        );
    }

    /**
     * Update the specified appointment
     */
    public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
    {
        $appointment = $this->appointmentService->update($id, $request->validated());

        return $this->successResponse(
            new AppointmentResource($appointment),
            __('appointment::messages.appointment_updated')
        );
    }

    /**
     * Remove the specified appointment
     */
    public function destroy(int $id): JsonResponse
    {
        $this->appointmentService->delete($id);

        return $this->successResponse(
            null,
            __('appointment::messages.appointment_deleted')
        );
    }

    /**
     * Confirm appointment
     */
    public function confirm(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->confirm($id);

        return $this->successResponse(
            new AppointmentResource($appointment),
            __('appointment::messages.appointment_confirmed')
        );
    }

    /**
     * Start appointment
     */
    public function start(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->start($id);

        return $this->successResponse(
            new AppointmentResource($appointment),
            __('appointment::messages.appointment_started')
        );
    }

    /**
     * Complete appointment
     */
    public function complete(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->complete($id);

        return $this->successResponse(
            new AppointmentResource($appointment),
            __('appointment::messages.appointment_completed')
        );
    }

    /**
     * Cancel appointment
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $reason = $request->input('reason');
        $appointment = $this->appointmentService->cancel($id, $reason);

        return $this->successResponse(
            new AppointmentResource($appointment),
            __('appointment::messages.appointment_cancelled')
        );
    }

    /**
     * Reschedule appointment
     */
    public function reschedule(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_date_time' => ['required', 'date'],
            'duration' => ['sometimes', 'integer', 'min:15', 'max:480'],
        ]);

        $appointment = $this->appointmentService->reschedule($id, $validated);

        return $this->successResponse(
            new AppointmentResource($appointment),
            __('appointment::messages.appointment_rescheduled')
        );
    }

    /**
     * Assign bay to appointment
     */
    public function assignBay(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'bay_id' => ['required', 'integer', 'exists:bays,id'],
            'start_time' => ['sometimes', 'date'],
            'end_time' => ['sometimes', 'date', 'after:start_time'],
            'notes' => ['nullable', 'string'],
        ]);

        $bayId = $validated['bay_id'];
        unset($validated['bay_id']);

        $appointment = $this->appointmentService->assignBay($id, $bayId, $validated);

        return $this->successResponse(
            new AppointmentResource($appointment),
            __('appointment::messages.bay_assigned')
        );
    }

    /**
     * Check availability
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'start_time' => ['required', 'date'],
            'duration' => ['required', 'integer', 'min:15', 'max:480'],
        ]);

        $availability = $this->appointmentService->checkAvailability(
            $validated['branch_id'],
            $validated['start_time'],
            $validated['duration']
        );

        return $this->successResponse(
            $availability,
            __('appointment::messages.availability_checked')
        );
    }

    /**
     * Get upcoming appointments
     */
    public function upcoming(): JsonResponse
    {
        $appointments = $this->appointmentService->getUpcoming();

        return $this->successResponse(
            AppointmentResource::collection($appointments),
            __('appointment::messages.appointments_retrieved')
        );
    }

    /**
     * Get appointments by status
     */
    public function byStatus(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $appointments = $this->appointmentService->getByStatus($status);

        return $this->successResponse(
            AppointmentResource::collection($appointments),
            __('appointment::messages.appointments_retrieved')
        );
    }

    /**
     * Search appointments
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $appointments = $this->appointmentService->search($query);

        return $this->successResponse(
            AppointmentResource::collection($appointments),
            __('appointment::messages.appointments_retrieved')
        );
    }
}
