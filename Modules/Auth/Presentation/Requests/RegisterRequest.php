<?php
namespace Modules\Auth\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'tenant_id' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
