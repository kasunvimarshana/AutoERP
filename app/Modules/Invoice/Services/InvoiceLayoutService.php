<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Repositories\EloquentConfigurationValueRepository;
use Modules\Configuration\Services\ResolveConfiguration;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceLayoutResolverInterface;
use Modules\Invoice\DTOs\InvoiceLayoutData;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceLayout;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class InvoiceLayoutService
{
    public function __construct(
        private TenantExecutionContextInterface $executionContext,
        private InvoiceLayoutProvisioner $provisioner,
        private InvoiceLayoutResolverInterface $resolver,
        private InvoicePrintService $prints,
        private EloquentConfigurationValueRepository $configurationRepository,
        private ConfigurationResolverInterface $configurationResolver,
        private DecimalMath $math,
    ) {}

    /** @return Collection<int, InvoiceLayout> */
    public function list(
        int $tenantId,
        ?string $search = null,
        ?string $paperSize = null,
        ?bool $isActive = null,
    ): Collection {
        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $search, $paperSize, $isActive): Collection {
            $count = InvoiceLayout::query()->where('tenant_id', $tenantId)->count();
            if ($count === 0) {
                $this->provisioner->provision($tenantId);
            }

            $query = InvoiceLayout::query()
                ->where('tenant_id', $tenantId)
                ->orderByDesc('is_system')
                ->orderBy('name');

            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }

            if ($paperSize !== null && trim($paperSize) !== '') {
                $query->where('paper_size', trim($paperSize));
            }

            if ($search !== null && trim($search) !== '') {
                $term = '%'.trim($search).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            }

            return $query->get();
        });
    }

    public function find(int $tenantId, int $id): ?InvoiceLayout
    {
        return $this->executionContext->runForTenant(
            $tenantId,
            function () use ($tenantId, $id): ?InvoiceLayout {
                if (InvoiceLayout::query()->where('tenant_id', $tenantId)->count() === 0) {
                    $this->provisioner->provision($tenantId);
                }

                return InvoiceLayout::query()
                    ->where('tenant_id', $tenantId)
                    ->find($id);
            },
        );
    }

    public function findByCode(int $tenantId, string $code): ?InvoiceLayout
    {
        return $this->executionContext->runForTenant(
            $tenantId,
            function () use ($tenantId, $code): ?InvoiceLayout {
                if (InvoiceLayout::query()->where('tenant_id', $tenantId)->count() === 0) {
                    $this->provisioner->provision($tenantId);
                }

                return InvoiceLayout::query()
                    ->where('tenant_id', $tenantId)
                    ->where('code', trim($code))
                    ->first();
            },
        );
    }

    public function create(int $tenantId, InvoiceLayoutData $data, int $userId): InvoiceLayout
    {
        $code = Str::slug($data->code, '_');
        if ($code === '') {
            throw ValidationException::withMessages(['code' => ['The layout code is invalid.']]);
        }

        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $code, $data, $userId): InvoiceLayout {
            $exists = InvoiceLayout::query()
                ->where('tenant_id', $tenantId)
                ->where('code', $code)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['code' => ['A layout with this code already exists.']]);
            }

            $this->validateCustomContent($data->customCss, $data->headerContent, $data->footerContent);

            $layout = new InvoiceLayout;
            $layout->tenant_id = $tenantId;
            $layout->code = $code;
            $layout->name = $data->name;
            $layout->description = $data->description;
            $layout->paper_size = $data->paperSize;
            $layout->orientation = $data->orientation;
            $layout->view_template = $data->viewTemplate;
            $layout->is_system = false;
            $layout->is_active = $data->isActive;
            $layout->custom_css = $data->customCss;
            $layout->header_content = $data->headerContent;
            $layout->footer_content = $data->footerContent;
            $layout->settings = $data->settings ?? [];
            $layout->row_version = 1;
            $layout->created_by = $userId;
            $layout->updated_by = $userId;
            $layout->save();

            return $layout;
        });
    }

    public function update(
        int $tenantId,
        int $id,
        InvoiceLayoutData $data,
        int $expectedVersion,
        int $userId,
    ): InvoiceLayout {
        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $id, $data, $expectedVersion, $userId): InvoiceLayout {
            $layout = InvoiceLayout::query()
                ->where('tenant_id', $tenantId)
                ->find($id);

            if ($layout === null) {
                throw new NotFoundHttpException('Invoice layout not found.');
            }

            if ((int) $layout->row_version !== $expectedVersion) {
                throw new ConflictHttpException(
                    "Invoice layout has been modified by another transaction. Expected version {$expectedVersion}, current version {$layout->row_version}."
                );
            }

            $this->validateCustomContent($data->customCss, $data->headerContent, $data->footerContent);

            if (! $layout->is_system) {
                $newCode = Str::slug($data->code, '_');
                if ($newCode !== $layout->code) {
                    $codeExists = InvoiceLayout::query()
                        ->where('tenant_id', $tenantId)
                        ->where('code', $newCode)
                        ->where('id', '!=', $id)
                        ->exists();

                    if ($codeExists) {
                        throw ValidationException::withMessages(['code' => ['A layout with this code already exists.']]);
                    }
                    $layout->code = $newCode;
                }
                $layout->paper_size = $data->paperSize;
                $layout->orientation = $data->orientation;
                $layout->view_template = $data->viewTemplate;
            }

            $layout->name = $data->name;
            $layout->description = $data->description;
            $layout->is_active = $data->isActive;
            $layout->custom_css = $data->customCss;
            $layout->header_content = $data->headerContent;
            $layout->footer_content = $data->footerContent;
            $layout->settings = $data->settings ?? $layout->settings;
            $layout->row_version = ((int) $layout->row_version) + 1;
            $layout->updated_by = $userId;
            $layout->save();

            return $layout;
        });
    }

    public function delete(int $tenantId, int $id): void
    {
        $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $id): void {
            $layout = InvoiceLayout::query()
                ->where('tenant_id', $tenantId)
                ->find($id);

            if ($layout === null) {
                throw new NotFoundHttpException('Invoice layout not found.');
            }

            if ($layout->is_system) {
                throw ValidationException::withMessages([
                    'layout' => ['System default invoice layouts are protected and cannot be deleted.'],
                ]);
            }

            if ($layout->invoices()->exists()) {
                throw ValidationException::withMessages([
                    'layout' => ['This layout cannot be deleted because it is assigned to existing invoices.'],
                ]);
            }

            $layout->delete();
        });
    }

    public function setDefault(int $tenantId, ?int $organizationUnitId, string $layoutCode, int $userId): void
    {
        $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $organizationUnitId, $layoutCode): void {
            if (InvoiceLayout::query()->where('tenant_id', $tenantId)->count() === 0) {
                $this->provisioner->provision($tenantId);
            }

            $layout = InvoiceLayout::query()
                ->where('tenant_id', $tenantId)
                ->where('code', trim($layoutCode))
                ->active()
                ->first();

            if ($layout === null) {
                throw ValidationException::withMessages([
                    'code' => ['The specified layout does not exist or is inactive.'],
                ]);
            }

            $scope = $organizationUnitId === null
                ? ConfigurationScope::TENANT
                : ConfigurationScope::ORGANIZATION_UNIT;

            $context = new ConfigurationScopeContext(
                scope: $scope,
                tenantId: $tenantId,
                organizationUnitId: $organizationUnitId,
            );

            $key = InvoiceLayoutResolverInterface::DEFAULT_LAYOUT_KEY;
            $existing = $this->configurationRepository->findExact($context, $key);
            $encodedValue = json_encode($layout->code, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($existing !== null) {
                $this->configurationRepository->updateExpected(
                    $existing,
                    $existing->rowVersion,
                    [
                        'value' => $encodedValue,
                        'value_type' => 'string',
                        'is_sensitive' => false,
                        'definition_version' => 1,
                    ],
                );
            } else {
                $this->configurationRepository->create($context, [
                    'key' => $key,
                    'value' => $encodedValue,
                    'value_type' => 'string',
                    'is_sensitive' => false,
                    'definition_version' => 1,
                ]);
            }

            if ($this->configurationResolver instanceof ResolveConfiguration) {
                $this->configurationResolver->forget($context, $key);
            }
        });
    }

    public function previewHtml(
        int $tenantId,
        int|string $layoutIdOrCode,
        ?int $invoiceId = null,
    ): string {
        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $layoutIdOrCode, $invoiceId): string {
            $layout = is_numeric($layoutIdOrCode)
                ? $this->find($tenantId, (int) $layoutIdOrCode)
                : $this->findByCode($tenantId, (string) $layoutIdOrCode);

            if ($layout === null) {
                throw new NotFoundHttpException('Invoice layout not found.');
            }

            if ($invoiceId !== null) {
                $invoice = Invoice::query()
                    ->where('tenant_id', $tenantId)
                    ->find($invoiceId);

                if ($invoice !== null) {
                    $viewData = $this->prints->viewData($invoice, mode: 'preview', requestedLayout: $layout);

                    return view('invoice.print', $viewData)->render();
                }
            }

            // Mock sample invoice data for realistic preview
            $sampleData = $this->sampleInvoiceViewData($layout);

            return view('invoice.print', $sampleData)->render();
        });
    }

    /** @return array<string, mixed> */
    public function sampleInvoiceViewData(InvoiceLayout $layout): array
    {
        $orientation = $layout->orientation;
        $paperSize = $layout->paper_size;
        $isCompact = $layout->isCompact();

        $currency = [
            'code' => 'USD',
            'symbol' => '$',
            'name' => 'US Dollar',
        ];

        $lines = [
            [
                'line_number' => 1,
                'reference' => 'SRV-001',
                'item_code' => 'SVC-MAJOR-01',
                'item' => 'Full Vehicle Diagnostic & Engine Service',
                'display_name' => 'Full Vehicle Diagnostic & Engine Service',
                'description' => 'Complete electronic multi-point diagnostics, oil flush, and engine tune-up.',
                'quantity' => ['raw' => '1.00', 'display' => '1.00'],
                'uom' => 'Service',
                'unit_price' => ['raw' => '180.000000', 'display' => '$180.00'],
                'discount_amount' => ['raw' => '10.000000', 'display' => '$10.00'],
                'tax_amount' => ['raw' => '13.600000', 'display' => '$13.60'],
                'charge_amount' => ['raw' => '0.000000', 'display' => '$0.00'],
                'line_total' => ['raw' => '170.000000', 'display' => '$170.00'],
            ],
            [
                'line_number' => 2,
                'reference' => 'PRT-SYN-OIL',
                'item_code' => 'OIL-5W30-SYN',
                'item' => 'Synthetic Motor Oil 5W-30 (4L)',
                'display_name' => 'Synthetic Motor Oil 5W-30 (4L)',
                'description' => 'High-performance fully synthetic motor oil formulated for extended drain intervals.',
                'quantity' => ['raw' => '1.00', 'display' => '1.00'],
                'uom' => 'Can',
                'unit_price' => ['raw' => '65.000000', 'display' => '$65.00'],
                'discount_amount' => ['raw' => '0.000000', 'display' => '$0.00'],
                'tax_amount' => ['raw' => '5.200000', 'display' => '$5.20'],
                'charge_amount' => ['raw' => '0.000000', 'display' => '$0.00'],
                'line_total' => ['raw' => '65.000000', 'display' => '$65.00'],
            ],
            [
                'line_number' => 3,
                'reference' => 'PRT-FLT-OIL',
                'item_code' => 'FLT-OEM-09',
                'item' => 'OEM Oil Filter Cartridge',
                'display_name' => 'OEM Oil Filter Cartridge',
                'description' => 'Original equipment manufacturer micro-filtration oil filter element.',
                'quantity' => ['raw' => '1.00', 'display' => '1.00'],
                'uom' => 'Each',
                'unit_price' => ['raw' => '25.000000', 'display' => '$25.00'],
                'discount_amount' => ['raw' => '0.000000', 'display' => '$0.00'],
                'tax_amount' => ['raw' => '2.000000', 'display' => '$2.00'],
                'charge_amount' => ['raw' => '0.000000', 'display' => '$0.00'],
                'line_total' => ['raw' => '25.000000', 'display' => '$25.00'],
            ],
        ];

        $amounts = [
            'subtotal' => ['label' => 'Total Value of Supply', 'raw' => '270.000000', 'display' => '$270.00'],
            'line_discount_total' => ['label' => 'Line discount', 'raw' => '10.000000', 'display' => '$10.00'],
            'bill_discount_total' => ['label' => 'Bill discount', 'raw' => '0.000000', 'display' => '$0.00'],
            'discount_total' => ['label' => 'Discounts', 'raw' => '10.000000', 'display' => '$10.00'],
            'tax_total' => ['label' => 'Tax Amount', 'raw' => '20.800000', 'display' => '$20.80'],
            'charge_total' => ['label' => 'Charges', 'raw' => '0.000000', 'display' => '$0.00'],
            'adjustment_total' => ['label' => 'Other adjustments', 'raw' => '0.000000', 'display' => '$0.00'],
            'grand_total' => ['label' => 'Total Amount including Tax', 'raw' => '280.800000', 'display' => '$280.80'],
            'paid_total' => ['label' => 'Paid', 'raw' => '280.800000', 'display' => '$280.80'],
            'credit_total' => ['label' => 'Credits', 'raw' => '0.000000', 'display' => '$0.00'],
            'balance_due' => ['label' => 'Balance due', 'raw' => '0.000000', 'display' => '$0.00'],
        ];

        return [
            'mode' => 'preview',
            'pdf_url' => null,
            'layout_model' => $layout,
            'layout' => [
                'code' => $layout->code,
                'name' => $layout->name,
                'paper_size' => $layout->paper_size,
                'orientation' => $layout->orientation,
                'view_template' => $layout->view_template,
                'is_compact' => $layout->isCompact(),
                'custom_css' => $layout->custom_css,
                'header_content' => $layout->header_content,
                'footer_content' => $layout->footer_content,
                'settings' => $layout->settings ?? [],
            ],
            'print_layout' => [
                'value' => $layout->toLegacyPrintLayout()->value,
                'css_class' => $layout->cssClass(),
                'paper_size' => $layout->paper_size,
                'orientation' => $layout->orientation,
                'css_page_size' => $layout->cssPageSize(),
                'css_page_margin' => $layout->cssPageMargin(),
                'continuous_width' => '215.9mm',
                'continuous_height' => '139.7mm',
                'continuous_horizontal_margin' => '13mm',
                'continuous_vertical_margin' => '4.2mm',
                'is_compact' => $isCompact,
                'is_outbound_a4' => $paperSize === 'a4',
                'is_outbound_continuous' => $paperSize === 'continuous_8_5_by_5_5',
                'service_content_min_height' => '112mm',
            ],
            'print_context' => [
                'printed_at' => (new DateTimeImmutable)->format('d M Y h:i A'),
                'printed_by' => 'System Preview',
                'copy_type' => 'Original',
            ],
            'document' => [
                'title' => 'Tax Invoice',
                'number_label' => 'Tax Invoice No.',
                'invoice_number' => 'INV-PREVIEW-2026-001',
                'invoice_date' => (new DateTimeImmutable)->format('Y-m-d'),
                'invoice_date_display' => (new DateTimeImmutable)->format('d M Y'),
                'due_date' => (new DateTimeImmutable)->modify('+30 days')->format('Y-m-d'),
                'invoice_type' => 'Service',
                'direction' => 'Outbound',
                'status' => 'Posted',
                'tax_label' => 'Tax',
                'currency' => $currency,
                'supplier' => [
                    'name' => 'AutoERP Prime Motors Ltd.',
                    'legal_name' => 'AutoERP Prime Motors Limited',
                    'tin' => '102938475',
                    'vat_registration_number' => 'VAT-99887766',
                    'svat_registration_number' => null,
                    'address' => '456 Automotive Blvd, Suite 100, Capital City',
                    'phone' => '+1 (555) 019-2834',
                ],
                'purchaser' => [
                    'name' => 'Acme Corporation Inc.',
                    'legal_name' => 'Acme Corporation Incorporated',
                    'tin' => '987654321',
                    'vat_registration_number' => 'VAT-11223344',
                    'svat_registration_number' => null,
                    'address' => '789 Enterprise Way, Tech District',
                    'phone' => '+1 (555) 432-8765',
                ],
                'purchaser_reference_fields' => [
                    ['label' => 'Vehicle Registration', 'value' => 'CAR-8822'],
                    ['label' => 'Make & Model', 'value' => 'Toyota Land Cruiser 300'],
                    ['label' => 'Mileage', 'value' => '42,500 km'],
                    ['label' => 'Job Order No.', 'value' => 'JOB-2026-0418'],
                ],
                'supply_date' => (new DateTimeImmutable)->format('Y-m-d'),
                'supply_period_start' => null,
                'supply_period_end' => null,
                'place_of_supply' => 'Main Workshop Facility',
                'payment_mode' => 'Credit Card',
                'resolved_payment_mode' => 'Credit Card / Visa ending in 4242',
                'payment_terms' => 'Net 30 Days',
                'amount_in_words' => 'Two Hundred Eighty Dollars and Eighty Cents Only',
                'notes' => 'Thank you for choosing AutoERP Prime Motors. All repairs carry a 12-month / 20,000 km warranty.',
                'warnings' => [],
                'uses_focused_print' => true,
                'capitalizes_company_header' => true,
                'shows_service_discount_breakdown' => true,
                'lines' => $lines,
                'amounts' => $amounts,
            ],
        ];
    }

    private function validateCustomContent(?string $css, ?string $header, ?string $footer): void
    {
        $all = ($css ?? '').' '.($header ?? '').' '.($footer ?? '');
        if (preg_match('/<script\b[^>]*>|javascript:/i', $all) === 1) {
            throw ValidationException::withMessages([
                'custom_content' => ['Custom content must not contain script tags or active JavaScript.'],
            ]);
        }
    }
}
