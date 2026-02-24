<?php
namespace Modules\Tenant\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'locale' => ['nullable', 'string', 'max:10'],
        ];
    }
}
