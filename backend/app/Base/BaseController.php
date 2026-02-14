<?php

namespace App\Base;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use Exception;

abstract class BaseController extends Controller
{
    protected BaseService $service;

    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $data = $this->service->paginate($perPage);
            
            return $this->successResponse($data, 'Records retrieved successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $data = $this->service->findById($id);
            
            if (!$data) {
                return $this->errorResponse('Record not found', 404);
            }
            
            return $this->successResponse($data, 'Record retrieved successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function store(FormRequest $request): JsonResponse
    {
        try {
            $data = $this->service->create($request->validated());
            
            return $this->successResponse($data, 'Record created successfully', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(FormRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->service->update($id, $request->validated());
            
            if (!$result) {
                return $this->errorResponse('Record not found or update failed', 404);
            }
            
            $data = $this->service->findById($id);
            
            return $this->successResponse($data, 'Record updated successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $result = $this->service->delete($id);
            
            if (!$result) {
                return $this->errorResponse('Record not found or delete failed', 404);
            }
            
            return $this->successResponse(null, 'Record deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    protected function errorResponse(string $message = 'Error', int $statusCode = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    protected function validationErrorResponse($errors): JsonResponse
    {
        return $this->errorResponse('Validation failed', 422, $errors);
    }
}
