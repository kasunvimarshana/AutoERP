<?php

declare(strict_types=1);

namespace Modules\Procurement\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
