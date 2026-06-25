<?php

declare(strict_types=1);
namespace Modules\Tenant\Http\Requests;
use Modules\Core\Http\Requests\QueryRequest;
use Modules\Tenant\Constants\TenantStatus;
final class ListTenantRequest extends QueryRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array { return [
        'status' => ['nullable','string','in:'.implode(',', TenantStatus::values())],
        'search' => ['nullable','string','max:255'],
        'per_page' => ['nullable','integer','min:1','max:100'],
        'page' => ['nullable','integer','min:1'],
    ]; }
}
