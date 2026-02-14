<?php

namespace App\Modules\Appointments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceBayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serviceBayId = $this->route('id');
        
        return [
            'bay_number' => 'required|string|max:50|unique:service_bays,bay_number,' . $serviceBayId,
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:10',
            'status' => 'sometimes|in:available,occupied,maintenance',
            'current_appointment_id' => 'nullable|exists:appointments,id',
        ];
    }

    public function messages(): array
    {
        return [
            'bay_number.required' => 'Bay number is required',
            'bay_number.unique' => 'This bay number is already in use',
            'name.required' => 'Name is required',
            'capacity.required' => 'Capacity is required',
            'capacity.min' => 'Capacity must be at least 1',
            'capacity.max' => 'Capacity cannot exceed 10',
        ];
    }
}
