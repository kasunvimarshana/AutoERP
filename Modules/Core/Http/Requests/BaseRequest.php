<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base API Request
 *
 * Provides a foundation for all API form requests with consistent
 * validation error handling and response formatting.
 *
 * This class ensures:
 * - JSON error responses for API requests
 * - Consistent error format across all endpoints
 * - Proper HTTP status codes
 * - Validation rule organization
 *
 * Usage:
 * 1. Extend this class in your module's requests
 * 2. Implement rules() method
 * 3. Optionally override authorize() for custom authorization
 *
 * Example:
 * class StoreCustomerRequest extends BaseRequest {
 *     public function rules(): array {
 *         return [
 *             'name' => 'required|string|max:255',
 *             'email' => 'required|email|unique:customers,email',
 *         ];
 *     }
 * }
 */
abstract class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Override this method in child classes for custom authorization logic.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     * Returns JSON response for API requests with validation errors.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }

    /**
     * Get custom attributes for validator errors.
     * Override this method to provide user-friendly attribute names.
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Get custom messages for validator errors.
     * Override this method to provide custom error messages.
     */
    public function messages(): array
    {
        return [];
    }
}
