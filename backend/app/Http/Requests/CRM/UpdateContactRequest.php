<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $contact = $this->route('contact');
        return $this->user()->can('update', $contact);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
            'is_decision_maker' => ['nullable', 'boolean'],
            'email_opt_in' => ['nullable', 'boolean'],
            'sms_opt_in' => ['nullable', 'boolean'],
            'phone_opt_in' => ['nullable', 'boolean'],
            'preferred_contact_method' => ['nullable', 'string', 'max:50'],
            'preferred_contact_time' => ['nullable', 'string', 'max:100'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'twitter_handle' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'birthday' => ['nullable', 'date'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Please provide a valid email address',
            'linkedin_url.url' => 'Please provide a valid LinkedIn URL',
        ];
    }
}
