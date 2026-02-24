<?php
namespace Modules\CRM\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\CRM\Domain\Enums\ActivityType;
use Modules\CRM\Domain\Enums\ActivityStatus;
class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(ActivityType::class)],
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['nullable', new Enum(ActivityStatus::class)],
            'assigned_to' => 'nullable|uuid',
            'related_type' => 'nullable|in:lead,opportunity,contact,account',
            'related_id' => 'nullable|uuid',
            'due_at' => 'nullable|date',
        ];
    }
}
