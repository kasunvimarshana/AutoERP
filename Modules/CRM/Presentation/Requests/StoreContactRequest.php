<?php
namespace Modules\CRM\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreContactRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'account_id' => 'nullable|uuid',
            'job_title' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'gdpr_consent' => 'nullable|boolean',
        ];
    }
}
