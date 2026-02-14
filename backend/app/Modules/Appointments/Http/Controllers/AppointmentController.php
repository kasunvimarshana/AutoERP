<?php

namespace App\Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appointments\Http\Requests\AppointmentRequest;
use App\Modules\Appointments\Http\Resources\AppointmentResource;
use App\Modules\Appointments\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $appointments = $this->appointmentService->paginate($perPage);
        
        return AppointmentResource::collection($appointments);
    }

    public function store(AppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointmentService->create($request->validated());
        
        return response()->json([
            'message' => 'Appointment created successfully',
            'data' => new AppointmentResource($appointment)
        ], 201);
    }

    public function show(int $id): AppointmentResource
    {
        $appointment = $this->appointmentService->findById($id);
        
        return new AppointmentResource($appointment);
    }

    public function update(AppointmentRequest $request, int $id): JsonResponse
    {
        $appointment = $this->appointmentService->update($id, $request->validated());
        
        return response()->json([
            'message' => 'Appointment updated successfully',
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->appointmentService->delete($id);
        
        return response()->json([
            'message' => 'Appointment deleted successfully'
        ]);
    }

    public function confirm(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->confirmAppointment($id);
        
        return response()->json([
            'message' => 'Appointment confirmed successfully',
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function start(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->startService($id);
        
        return response()->json([
            'message' => 'Service started successfully',
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function complete(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->completeAppointment($id);
        
        return response()->json([
            'message' => 'Appointment completed successfully',
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->cancelAppointment($id);
        
        return response()->json([
            'message' => 'Appointment cancelled successfully',
            'data' => new AppointmentResource($appointment)
        ]);
    }
}
