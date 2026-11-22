<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Invoice\Enums\InvoicePaperSize;
use Modules\Invoice\Models\InvoiceLayout;

/**
 * @mixin InvoiceLayout
 */
final class InvoiceLayoutResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'description' => $this->description,
            'paper_size' => (string) $this->paper_size,
            'paper_size_label' => InvoicePaperSize::tryFrom((string) $this->paper_size)?->label() ?? (string) $this->paper_size,
            'orientation' => (string) $this->orientation,
            'view_template' => (string) $this->view_template,
            'is_system' => (bool) $this->is_system,
            'is_active' => (bool) $this->is_active,
            'custom_css' => $this->custom_css,
            'header_content' => $this->header_content,
            'footer_content' => $this->footer_content,
            'settings' => $this->settings ?? [],
            'is_tenant_default' => (bool) ($this->getAttribute('is_tenant_default') ?? false),
            'is_branch_default' => (bool) ($this->getAttribute('is_branch_default') ?? false),
            'row_version' => (int) $this->row_version,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
