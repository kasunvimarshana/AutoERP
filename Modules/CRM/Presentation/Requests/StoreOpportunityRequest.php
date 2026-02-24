<?php
namespace Modules\CRM\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'lead_id' => 'nullable|uuid',
            'contact_id' => 'nullable|uuid',
            'account_id' => 'nullable|uuid',
            'stage' => 'nullable|string|max:100',
            'expected_revenue' => 'nullable|numeric|min:0',
            'probability' => 'nullable|numeric|min:0|max:100',
            'assigned_to' => 'nullable|uuid',
            'expected_close_date' => 'nullable|date',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string',
        ];
    }
}
