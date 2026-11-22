<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Services;

use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Invoice\Contracts\InvoiceLayoutResolverInterface;
use Modules\Invoice\Enums\InvoicePrintLayout;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceLayout;

final readonly class InvoiceLayoutResolver implements InvoiceLayoutResolverInterface
{
    public function __construct(
        private ConfigurationResolverInterface $configuration,
        private TenantExecutionContextInterface $executionContext,
        private InvoiceLayoutProvisioner $provisioner,
    ) {}

    public function resolve(Invoice $invoice, ?string $explicitLayoutCode = null, ?string $printer = null): InvoiceLayout
    {
        $tenantId = (int) $invoice->tenant_id;
        $organizationUnitId = $invoice->organization_unit_id === null
            ? null
            : (int) $invoice->organization_unit_id;

        return $this->executionContext->runForTenant($tenantId, function () use ($invoice, $tenantId, $organizationUnitId, $explicitLayoutCode, $printer): InvoiceLayout {
            $this->ensureProvisioned($tenantId);

            if ($explicitLayoutCode !== null && trim($explicitLayoutCode) !== '') {
                $layout = InvoiceLayout::query()
                    ->where('tenant_id', $tenantId)
                    ->where('code', trim($explicitLayoutCode))
                    ->active()
                    ->first();

                if ($layout instanceof InvoiceLayout) {
                    return $layout;
                }
            }

            if ($printer !== null && trim($printer) !== '') {
                $printerLayout = $this->resolvePrinterLayout($tenantId, $organizationUnitId, trim($printer));
                if ($printerLayout instanceof InvoiceLayout) {
                    return $printerLayout;
                }
            }

            if ($invoice->invoice_layout_id !== null) {
                $layout = InvoiceLayout::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', (int) $invoice->invoice_layout_id)
                    ->active()
                    ->first();

                if ($layout instanceof InvoiceLayout) {
                    return $layout;
                }
            }

            return $this->resolveForScopeInternal($tenantId, $organizationUnitId, $printer);
        });
    }

    public function resolveForScope(int $tenantId, ?int $organizationUnitId = null, ?string $printer = null): InvoiceLayout
    {
        return $this->executionContext->runForTenant(
            $tenantId,
            fn (): InvoiceLayout => $this->resolveForScopeInternal($tenantId, $organizationUnitId, $printer),
        );
    }

    private function resolvePrinterLayout(int $tenantId, ?int $organizationUnitId, string $printer): ?InvoiceLayout
    {
        $printerMap = $this->configurationValue($tenantId, $organizationUnitId, self::PRINTER_LAYOUTS_KEY);
        if (is_array($printerMap) && isset($printerMap[$printer]) && is_string($printerMap[$printer]) && trim($printerMap[$printer]) !== '') {
            $layout = InvoiceLayout::query()
                ->where('tenant_id', $tenantId)
                ->where('code', trim($printerMap[$printer]))
                ->active()
                ->first();

            if ($layout instanceof InvoiceLayout) {
                return $layout;
            }
        }

        return null;
    }

    private function resolveForScopeInternal(int $tenantId, ?int $organizationUnitId, ?string $printer = null): InvoiceLayout
    {
        $this->ensureProvisioned($tenantId);

        // 0. Try printer-specific layout if printer is supplied
        if ($printer !== null && trim($printer) !== '') {
            $printerLayout = $this->resolvePrinterLayout($tenantId, $organizationUnitId, trim($printer));
            if ($printerLayout instanceof InvoiceLayout) {
                return $printerLayout;
            }
        }

        // 1. Try invoice.default_layout configuration
        $configuredCode = $this->configurationValue($tenantId, $organizationUnitId, self::DEFAULT_LAYOUT_KEY);
        if (is_string($configuredCode) && trim($configuredCode) !== '') {
            $layout = InvoiceLayout::query()
                ->where('tenant_id', $tenantId)
                ->where('code', trim($configuredCode))
                ->active()
                ->first();

            if ($layout instanceof InvoiceLayout) {
                return $layout;
            }
        }

        // 2. Fallback to legacy invoice.default_print_layout configuration
        $legacyValue = $this->configurationValue($tenantId, $organizationUnitId, InvoicePrintLayout::CONFIGURATION_KEY);
        if (is_string($legacyValue) && trim($legacyValue) !== '') {
            $mappedCode = match (trim($legacyValue)) {
                InvoicePrintLayout::CompactA5->value => InvoiceLayoutProvisioner::SYSTEM_COMPACT_A5,
                InvoicePrintLayout::Continuous85By55->value => InvoiceLayoutProvisioner::SYSTEM_CONTINUOUS,
                default => InvoiceLayoutProvisioner::SYSTEM_STANDARD_A4,
            };

            $layout = InvoiceLayout::query()
                ->where('tenant_id', $tenantId)
                ->where('code', $mappedCode)
                ->active()
                ->first();

            if ($layout instanceof InvoiceLayout) {
                return $layout;
            }
        }

        // 3. Fallback to standard_a4
        $layout = InvoiceLayout::query()
            ->where('tenant_id', $tenantId)
            ->where('code', InvoiceLayoutProvisioner::SYSTEM_STANDARD_A4)
            ->active()
            ->first();

        if ($layout instanceof InvoiceLayout) {
            return $layout;
        }

        return InvoiceLayout::query()
            ->where('tenant_id', $tenantId)
            ->where('code', InvoiceLayoutProvisioner::SYSTEM_STANDARD_A4)
            ->firstOrFail();
    }

    private function ensureProvisioned(int $tenantId): void
    {
        $count = InvoiceLayout::query()->where('tenant_id', $tenantId)->count();
        if ($count === 0) {
            $this->provisioner->provision($tenantId);
        }
    }

    private function configurationValue(int $tenantId, ?int $organizationUnitId, string $key): mixed
    {
        return $this->configuration->value($key, $tenantId, $organizationUnitId);
    }
}
