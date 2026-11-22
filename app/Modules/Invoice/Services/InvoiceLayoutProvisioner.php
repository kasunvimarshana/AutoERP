<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Invoice\Enums\InvoiceLayoutTemplate;
use Modules\Invoice\Enums\InvoiceOrientation;
use Modules\Invoice\Enums\InvoicePaperSize;
use Modules\Invoice\Models\InvoiceLayout;

final readonly class InvoiceLayoutProvisioner
{
    public const SYSTEM_STANDARD_A4 = 'standard_a4';
    public const SYSTEM_MODERN_A4 = 'modern_a4';
    public const SYSTEM_COMPACT_A5 = 'compact_a5';
    public const SYSTEM_POS_THERMAL = 'pos_thermal_80mm';
    public const SYSTEM_CONTINUOUS = 'continuous_8_5_by_5_5';

    public function __construct(
        private TenantExecutionContextInterface $executionContext,
    ) {}

    /** @return list<InvoiceLayout> */
    public function provision(int $tenantId): array
    {
        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId): array {
            $created = [];
            foreach ($this->defaultLayoutDefinitions() as $def) {
                $layout = InvoiceLayout::withTrashed()->firstOrNew([
                    'tenant_id' => $tenantId,
                    'code' => $def['code'],
                ]);

                $layout->forceFill([
                    'tenant_id' => $tenantId,
                    'code' => $def['code'],
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'paper_size' => $def['paper_size'],
                    'orientation' => $def['orientation'],
                    'view_template' => $def['view_template'],
                    'is_system' => true,
                    'is_active' => true,
                    'settings' => $def['settings'],
                    'deleted_at' => null,
                ]);

                if (! $layout->exists) {
                    $layout->row_version = 1;
                    $layout->save();
                }

                $created[] = $layout;
            }

            return $created;
        });
    }

    /** @return list<array<string, mixed>> */
    public function defaultLayoutDefinitions(): array
    {
        return [
            [
                'code' => self::SYSTEM_STANDARD_A4,
                'name' => 'Standard Classic A4',
                'description' => 'Formal corporate A4 portrait layout with complete tax breakdown, line references, and authorized signatures.',
                'paper_size' => InvoicePaperSize::A4->value,
                'orientation' => InvoiceOrientation::Portrait->value,
                'view_template' => InvoiceLayoutTemplate::Standard->value,
                'settings' => [
                    'primary_color' => '#111827',
                    'font_family' => 'serif',
                    'show_company_logo' => true,
                    'show_tax_summary' => true,
                    'show_payment_details' => true,
                    'show_signatures' => true,
                    'show_amount_in_words' => true,
                    'show_notes' => true,
                ],
            ],
            [
                'code' => self::SYSTEM_MODERN_A4,
                'name' => 'Modern Professional A4',
                'description' => 'Contemporary A4 portrait design featuring clean accent headers, distinct total summary cards, and modern typography.',
                'paper_size' => InvoicePaperSize::A4->value,
                'orientation' => InvoiceOrientation::Portrait->value,
                'view_template' => InvoiceLayoutTemplate::Modern->value,
                'settings' => [
                    'primary_color' => '#173956',
                    'font_family' => 'sans-serif',
                    'show_company_logo' => true,
                    'show_tax_summary' => true,
                    'show_payment_details' => true,
                    'show_signatures' => true,
                    'show_amount_in_words' => true,
                    'show_notes' => true,
                ],
            ],
            [
                'code' => self::SYSTEM_COMPACT_A5,
                'name' => 'Compact Slip A5',
                'description' => 'Condensed A5 portrait receipt format designed for rapid counter counter transactions and minimal paper consumption.',
                'paper_size' => InvoicePaperSize::A5->value,
                'orientation' => InvoiceOrientation::Portrait->value,
                'view_template' => InvoiceLayoutTemplate::Compact->value,
                'settings' => [
                    'primary_color' => '#1e293b',
                    'font_family' => 'sans-serif',
                    'show_company_logo' => false,
                    'show_tax_summary' => false,
                    'show_payment_details' => true,
                    'show_signatures' => true,
                    'show_amount_in_words' => false,
                    'show_notes' => true,
                ],
            ],
            [
                'code' => self::SYSTEM_POS_THERMAL,
                'name' => 'Thermal POS Receipt 80mm',
                'description' => 'Continuous 80mm roll format tailored for point-of-sale thermal receipt printers with condensed monospace line items.',
                'paper_size' => InvoicePaperSize::Thermal80mm->value,
                'orientation' => InvoiceOrientation::Portrait->value,
                'view_template' => InvoiceLayoutTemplate::Thermal->value,
                'settings' => [
                    'primary_color' => '#000000',
                    'font_family' => 'monospace',
                    'show_company_logo' => false,
                    'show_tax_summary' => true,
                    'show_payment_details' => true,
                    'show_signatures' => false,
                    'show_amount_in_words' => false,
                    'show_notes' => true,
                ],
            ],
            [
                'code' => self::SYSTEM_CONTINUOUS,
                'name' => 'Continuous Feed 8.5 × 5.5',
                'description' => 'Specialized landscape continuous form layout designed for dot-matrix multi-part pre-printed and continuous carbon stationery.',
                'paper_size' => InvoicePaperSize::Continuous85By55->value,
                'orientation' => InvoiceOrientation::Landscape->value,
                'view_template' => InvoiceLayoutTemplate::Continuous->value,
                'settings' => [
                    'primary_color' => '#111827',
                    'font_family' => 'sans-serif',
                    'show_company_logo' => true,
                    'show_tax_summary' => true,
                    'show_payment_details' => true,
                    'show_signatures' => true,
                    'show_amount_in_words' => false,
                    'show_notes' => true,
                ],
            ],
        ];
    }
}
