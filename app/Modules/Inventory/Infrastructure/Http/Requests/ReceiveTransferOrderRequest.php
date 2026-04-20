<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveTransferOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'lines' => 'required|array|min:1',
            'lines.*.line_id' => 'required|integer',
            'lines.*.received_qty' => 'required|numeric|min:0.000001',
        ];
    }
}
