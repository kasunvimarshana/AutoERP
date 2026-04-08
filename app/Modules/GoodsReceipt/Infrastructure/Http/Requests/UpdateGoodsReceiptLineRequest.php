<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoodsReceiptLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'quantity_accepted'   => 'required|numeric|min:0',
            'quantity_rejected'   => 'required|numeric|min:0',
            'condition'           => 'required|string|in:good,damaged,expired,quarantine',
            'putaway_location_id' => 'nullable|integer',
            'notes'               => 'nullable|string',
            'metadata'            => 'nullable|array',
        ];
    }
}
