<?php

declare(strict_types=1);

namespace Modules\Warehouse\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertWarehouseLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [

        ];
    }
}