<?php

declare(strict_types=1);

namespace App\Modules\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = \App\Modules\POS\Models\POSSession::find($this->route('id'));
        return $session && $this->user()->can('update', $session);
    }

    public function rules(): array
    {
        return [
            'closing_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
