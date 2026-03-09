<?php
declare(strict_types=1);
namespace App\Http\Requests\Notification;
use Illuminate\Foundation\Http\FormRequest;
class SendNotificationRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'tenant_id' => ['required', 'string'],
            'customer_id' => ['required', 'string'],
            'event' => ['required', 'string'],
            'template' => ['nullable', 'string'],
            'channel' => ['nullable', 'in:email,sms,webhook,push'],
            'saga_id' => ['nullable', 'uuid'],
            'recipient' => ['nullable', 'string'],
        ];
    }
}
