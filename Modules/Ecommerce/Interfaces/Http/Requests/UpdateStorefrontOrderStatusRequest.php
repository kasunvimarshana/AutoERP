<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\Domain\Enums\StorefrontOrderStatus;

class UpdateStorefrontOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = implode(',', array_column(StorefrontOrderStatus::cases(), 'value'));

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:'.$statuses],
        ];
    }
}
