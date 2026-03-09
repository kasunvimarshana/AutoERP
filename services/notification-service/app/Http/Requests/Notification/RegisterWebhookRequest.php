<?php
declare(strict_types=1);
namespace App\Http\Requests\Notification;
use Illuminate\Foundation\Http\FormRequest;
class RegisterWebhookRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'secret' => ['required', 'string', 'min:16'],
            'events' => ['required', 'array'],
            'events.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
            'headers' => ['nullable', 'array'],
        ];
    }
}
