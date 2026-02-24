<?php

namespace Modules\Recruitment\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position_id'    => ['required', 'uuid'],
            'candidate_name' => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:50'],
            'resume_url'     => ['nullable', 'url', 'max:2048'],
            'cover_letter'   => ['nullable', 'string'],
            'source'         => ['nullable', 'string', 'max:100'],
        ];
    }
}
