<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventorySettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'valuation_method'      => 'sometimes|string|in:fifo,lifo,avco,standard_cost,specific_identification',
            'management_method'     => 'sometimes|string|in:perpetual,periodic',
            'rotation_strategy'     => 'sometimes|string|in:fefo,fifo,lifo,manual',
            'allocation_algorithm'  => 'sometimes|string|in:fefo,fifo,lifo,nearest_expiry,manual',
            'cycle_count_method'    => 'sometimes|string|in:abc,frequency,random,manual',
            'negative_stock_allowed'=> 'sometimes|boolean',
            'track_lots'            => 'sometimes|boolean',
            'track_serial_numbers'  => 'sometimes|boolean',
            'track_expiry'          => 'sometimes|boolean',
            'auto_reorder'          => 'sometimes|boolean',
            'low_stock_alert'       => 'sometimes|boolean',
            'metadata'              => 'nullable|array',
            'is_active'             => 'sometimes|boolean',
        ];
    }
}
