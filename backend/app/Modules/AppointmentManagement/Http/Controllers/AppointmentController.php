<?php

namespace App\Modules\AppointmentManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\AppointmentManagement\Http\Requests\StoreAppointmentRequest;
use App\Modules\AppointmentManagement\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends BaseController
{
    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * Display a listing of appointments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'service_bay_id' => $request->input('service_bay_id'),
                'customer_id' => $request->input('customer_id'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $appointments = $this->appointmentService->search($criteria);

            return $this->success($appointments);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created appointment
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;

            $appointment = $this->appointmentService->create($data);

            return $this->created($appointment, 'Appointment created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified appointment
     */
    public function show(int $id): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->findByIdOrFail($id);
            $appointment->load(['customer', 'vehicle', 'serviceBay']);

            return $this->success($appointment);
        } catch (\Exception $e) {
            return $this->notFound('Appointment not found');
        }
    }

    /**
     * Update the specified appointment
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->update($id, $request->all());

            return $this->success($appointment, 'Appointment updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified appointment
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->appointmentService->delete($id);

            return $this->success(null, 'Appointment deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Confirm an appointment
     */
    public function confirm(int $id): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->confirm($id);

            return $this->success($appointment, 'Appointment confirmed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->cancel($id, $request->input('reason'));

            return $this->success($appointment, 'Appointment cancelled successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Complete an appointment
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->complete($id);

            return $this->success($appointment, 'Appointment completed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
