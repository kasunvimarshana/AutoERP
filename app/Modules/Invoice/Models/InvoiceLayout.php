<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\Concerns\HasTenantScope;
use Modules\Invoice\Enums\InvoiceOrientation;
use Modules\Invoice\Enums\InvoicePaperSize;
use Modules\Invoice\Enums\InvoicePrintLayout;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\UserModel;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $paper_size
 * @property string $orientation
 * @property string $view_template
 * @property bool $is_system
 * @property bool $is_active
 * @property string|null $custom_css
 * @property string|null $header_content
 * @property string|null $footer_content
 * @property array<string, mixed>|null $settings
 * @property int $row_version
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class InvoiceLayout extends Model
{
    use HasFactory;
    use HasTenantScope;
    use SoftDeletes;

    protected $table = 'invoice_layouts';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'paper_size',
        'orientation',
        'view_template',
        'is_system',
        'is_active',
        'custom_css',
        'header_content',
        'footer_content',
        'settings',
        'row_version',
        'created_by',
        'updated_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'settings' => 'array',
            'row_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'invoice_layout_id');
    }

    /** @return BelongsTo<UserModel, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }

    /** @return BelongsTo<UserModel, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'updated_by');
    }

    /** @param Builder<$this> $query @return Builder<$this> */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function cssClass(): string
    {
        return match ($this->paper_size) {
            InvoicePaperSize::A5->value => 'layout-a5',
            InvoicePaperSize::Continuous85By55->value => 'layout-continuous',
            InvoicePaperSize::Thermal80mm->value => 'layout-thermal',
            default => 'layout-a4',
        };
    }

    public function cssPageSize(): string
    {
        $orientation = $this->orientation === InvoiceOrientation::Landscape->value ? 'landscape' : 'portrait';

        return match ($this->paper_size) {
            InvoicePaperSize::A4->value => 'A4 '.$orientation,
            InvoicePaperSize::A5->value => 'A5 '.$orientation,
            InvoicePaperSize::Thermal80mm->value => '80mm auto',
            InvoicePaperSize::Continuous85By55->value => '215.9mm 139.7mm',
            InvoicePaperSize::Letter->value => 'Letter '.$orientation,
            default => 'A4 portrait',
        };
    }

    public function cssPageMargin(): string
    {
        return match ($this->paper_size) {
            InvoicePaperSize::A5->value => '6mm',
            InvoicePaperSize::Thermal80mm->value => '3mm 2mm',
            InvoicePaperSize::Continuous85By55->value => '4.2mm 13mm 4.2mm 12mm',
            default => '10mm',
        };
    }

    /** @return string|array{0: float, 1: float, 2: float, 3: float} */
    public function pdfPaper(): string|array
    {
        if ($this->paper_size === InvoicePaperSize::Continuous85By55->value) {
            return [
                0.0,
                0.0,
                139.7 * 72 / 25.4,
                215.9 * 72 / 25.4,
            ];
        }

        if ($this->paper_size === InvoicePaperSize::Thermal80mm->value) {
            return [
                0.0,
                0.0,
                80.0 * 72 / 25.4,
                297.0 * 72 / 25.4,
            ];
        }

        return match ($this->paper_size) {
            InvoicePaperSize::A5->value => 'A5',
            InvoicePaperSize::Letter->value => 'letter',
            default => 'A4',
        };
    }

    public function isCompact(): bool
    {
        return in_array($this->paper_size, [
            InvoicePaperSize::A5->value,
            InvoicePaperSize::Thermal80mm->value,
            InvoicePaperSize::Continuous85By55->value,
        ], true);
    }

    /** @return InvoicePrintLayout */
    public function toLegacyPrintLayout(): InvoicePrintLayout
    {
        return match ($this->paper_size) {
            InvoicePaperSize::A5->value => InvoicePrintLayout::CompactA5,
            InvoicePaperSize::Continuous85By55->value => InvoicePrintLayout::Continuous85By55,
            default => InvoicePrintLayout::StandardA4,
        };
    }
}
