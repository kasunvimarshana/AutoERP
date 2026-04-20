<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectApprovalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolved_by_user_id' => ['required', 'integer', 'exists:users,id'],
            'comments' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
