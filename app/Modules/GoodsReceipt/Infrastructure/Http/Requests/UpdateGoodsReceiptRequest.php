<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes'         => 'nullable|string',
            'metadata'      => 'nullable|array',
            'received_date' => 'nullable|date',
            'warehouse_id'  => 'nullable|integer',
        ];
    }
}
