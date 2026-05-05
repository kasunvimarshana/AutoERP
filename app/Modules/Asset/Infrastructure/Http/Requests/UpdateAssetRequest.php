<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:active,maintenance,retired,sold,damaged',
        ];
    }
}
