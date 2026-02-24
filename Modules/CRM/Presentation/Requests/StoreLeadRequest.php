<?php
namespace Modules\CRM\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\CRM\Domain\Enums\LeadSource;
use Modules\CRM\Domain\Enums\LeadStatus;
class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'source' => ['nullable', new Enum(LeadSource::class)],
            'status' => ['nullable', new Enum(LeadStatus::class)],
            'score' => 'nullable|numeric|min:0|max:100',
            'assigned_to' => 'nullable|uuid',
            'campaign' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
