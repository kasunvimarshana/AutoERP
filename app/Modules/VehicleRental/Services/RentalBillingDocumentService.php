<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceAdjustment;
use Modules\Invoice\Models\InvoiceLine;
use Modules\Invoice\Services\InvoiceBalanceService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Models\PaymentAllocation;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseChargeSource;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\BillingFinalizationMode;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\UsageChargeSource;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Models\RentalExpenseAllocation;

final class RentalBillingDocumentService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
        private readonly InvoiceStatusService $statusService,
        private readonly InvoiceBalanceService $balanceService,
    ) {}

    /**
     * Build base query strictly scoped to tenant and rental operations.
     */
    public function baseRentalInvoiceQuery(AgreementContext $context): Builder
    {
        return Invoice::query()
            ->where('invoices.tenant_id', $context->tenantId)
            ->where('invoices.organization_unit_id', $context->organizationUnitId)
            ->where(function (Builder $q): void {
                $q->whereHas('sources', function (Builder $sq): void {
                    $sq->where('source_type', 'like', 'vehicle_rental_%')
                        ->orWhere('source_type', 'like', 'rental_%');
                })
                ->orWhereExists(function ($sub): void {
                    $sub->selectRaw(1)
                        ->from('vehicle_rental_billing_cycles')
                        ->whereColumn('vehicle_rental_billing_cycles.initial_invoice_id', 'invoices.id')
                        ->orWhereColumn('vehicle_rental_billing_cycles.supplementary_invoice_id', 'invoices.id')
                        ->orWhereColumn('vehicle_rental_billing_cycles.final_invoice_id', 'invoices.id');
                });
            });
    }

    public function listDocuments(AgreementContext $context, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $direction = $filters['direction'] ?? null;
        $this->assertViewPermission($context, $direction);

        $query = $this->baseRentalInvoiceQuery($context);

        if ($direction !== null && $direction !== '') {
            $query->where('direction', $direction);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('invoice_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['party_id'])) {
            $query->where('party_id', (int) $filters['party_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $cq) use ($search): void {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('supplier', function (Builder $sq) use ($search): void {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('sources', function (Builder $sq) use ($search): void {
                        $sq->where('source_document_number', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['agreement_id'])) {
            $agreementId = (int) $filters['agreement_id'];
            $custAgr = CustomerAgreement::query()->where('tenant_id', $context->tenantId)->where('id', $agreementId)->first();
            $ownAgr = OwnerAgreement::query()->where('tenant_id', $context->tenantId)->where('id', $agreementId)->first();
            $agreementRef = $custAgr?->reference ?? $ownAgr?->reference;

            $query->where(function (Builder $q) use ($agreementId, $agreementRef): void {
                $q->whereHas('sources', function (Builder $sq) use ($agreementId, $agreementRef): void {
                    if ($agreementRef) {
                        $sq->where('source_document_number', $agreementRef)
                            ->orWhere('source_document_number', 'like', $agreementRef.'-%');
                    }
                    $sq->orWhere(function (Builder $ssq) use ($agreementId): void {
                        $ssq->where('source_type', 'vehicle_rental_owner_settlement')
                            ->where('source_id', $agreementId);
                    });
                })
                ->orWhereExists(function ($sub) use ($agreementId): void {
                    $sub->selectRaw(1)
                        ->from('vehicle_rental_billing_cycles')
                        ->where(function ($w) use ($agreementId): void {
                            $w->where('customer_agreement_id', $agreementId)
                                ->orWhere('owner_agreement_id', $agreementId);
                        })
                        ->where(function ($w): void {
                            $w->whereColumn('vehicle_rental_billing_cycles.initial_invoice_id', 'invoices.id')
                                ->orWhereColumn('vehicle_rental_billing_cycles.supplementary_invoice_id', 'invoices.id')
                                ->orWhereColumn('vehicle_rental_billing_cycles.final_invoice_id', 'invoices.id');
                        });
                });
            });
        }

        if (! empty($filters['balance_status'])) {
            $today = CarbonImmutable::now()->toDateString();
            match ($filters['balance_status']) {
                'paid' => $query->where('balance_due', '<=', 0)
                    ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value]),
                'unpaid' => $query->where('balance_due', '>', 0)
                    ->where('paid_total', '<=', 0)
                    ->where(function (Builder $b) use ($today): void {
                        $b->whereNull('due_date')->orWhereDate('due_date', '>=', $today);
                    })
                    ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value]),
                'partially_paid' => $query->where('balance_due', '>', 0)
                    ->where('paid_total', '>', 0)
                    ->where(function (Builder $b) use ($today): void {
                        $b->whereNull('due_date')->orWhereDate('due_date', '>=', $today);
                    })
                    ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value]),
                'overdue' => $query->where('balance_due', '>', 0)
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', $today)
                    ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value]),
                default => null,
            };
        }

        $query->with([
            'customer',
            'supplier',
            'currency',
            'balance',
            'adjustments',
            'sources',
        ])
        ->orderByDesc('invoice_date')
        ->orderByDesc('id');

        $paginated = $query->paginate($perPage);

        $contexts = $this->bulkLoadRentalContexts($context, $paginated->getCollection());

        return $paginated->through(function (Invoice $invoice) use ($contexts): array {
            return $this->formatDocumentItem($invoice, $contexts[$invoice->id] ?? null);
        });
    }

    public function getSummary(AgreementContext $context): array
    {
        $this->assertViewPermission($context, null);

        $today = CarbonImmutable::now()->toDateString();

        // Customer AR (Outbound)
        $outboundInvoices = $this->baseRentalInvoiceQuery($context)
            ->where('direction', InvoiceDirection::Outbound->value)
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value])
            ->with(['balance'])
            ->get();

        $arTotalInvoiced = '0.000000';
        $arTotalCollected = '0.000000';
        $arTotalOutstanding = '0.000000';
        $arOverdueCount = 0;
        $arOverdueAmount = '0.000000';

        foreach ($outboundInvoices as $inv) {
            $grand = (string) $inv->grand_total;
            $paid = (string) ($inv->paid_total ?? $inv->balance?->paid_amount ?? '0.000000');
            $rem = (string) ($inv->balance_due ?? $inv->balance?->remaining_amount ?? '0.000000');

            $arTotalInvoiced = $this->math->add($arTotalInvoiced, $grand);
            $arTotalCollected = $this->math->add($arTotalCollected, $paid);
            $arTotalOutstanding = $this->math->add($arTotalOutstanding, $rem);

            if ($inv->due_date !== null && $inv->due_date->toDateString() < $today && $this->math->compare($rem, '0.000000') > 0) {
                $arOverdueCount++;
                $arOverdueAmount = $this->math->add($arOverdueAmount, $rem);
            }
        }

        // Owner AP (Inbound)
        $inboundInvoices = $this->baseRentalInvoiceQuery($context)
            ->where('direction', InvoiceDirection::Inbound->value)
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value])
            ->with(['balance', 'adjustments'])
            ->get();

        $apTotalGrossBilled = '0.000000';
        $apTotalWhtWithheld = '0.000000';
        $apTotalNetPaid = '0.000000';
        $apTotalOutstanding = '0.000000';
        $apOverdueCount = 0;
        $apOverdueAmount = '0.000000';

        foreach ($inboundInvoices as $inv) {
            $gross = (string) $inv->subtotal;
            $paid = (string) ($inv->paid_total ?? $inv->balance?->paid_amount ?? '0.000000');
            $rem = (string) ($inv->balance_due ?? $inv->balance?->remaining_amount ?? '0.000000');

            $wht = '0.000000';
            foreach ($inv->adjustments as $adj) {
                $adjType = $adj->adjustment_type instanceof AdjustmentType ? $adj->adjustment_type->value : (string) $adj->adjustment_type;
                if ($adjType === AdjustmentType::Withholding->value || $adjType === 'withholding') {
                    $wht = $this->math->add($wht, (string) $adj->amount);
                }
            }

            $apTotalGrossBilled = $this->math->add($apTotalGrossBilled, $gross);
            $apTotalWhtWithheld = $this->math->add($apTotalWhtWithheld, $wht);
            $apTotalNetPaid = $this->math->add($apTotalNetPaid, $paid);
            $apTotalOutstanding = $this->math->add($apTotalOutstanding, $rem);

            if ($inv->due_date !== null && $inv->due_date->toDateString() < $today && $this->math->compare($rem, '0.000000') > 0) {
                $apOverdueCount++;
                $apOverdueAmount = $this->math->add($apOverdueAmount, $rem);
            }
        }

        return [
            'customer_ar' => [
                'total_invoiced' => $arTotalInvoiced,
                'total_collected' => $arTotalCollected,
                'total_outstanding' => $arTotalOutstanding,
                'overdue_count' => $arOverdueCount,
                'overdue_amount' => $arOverdueAmount,
            ],
            'owner_ap' => [
                'total_gross_billed' => $apTotalGrossBilled,
                'total_wht_withheld' => $apTotalWhtWithheld,
                'total_net_paid' => $apTotalNetPaid,
                'total_outstanding' => $apTotalOutstanding,
                'overdue_count' => $apOverdueCount,
                'overdue_amount' => $apOverdueAmount,
            ],
        ];
    }

    public function getDocumentDetail(AgreementContext $context, int $invoiceId): array
    {
        $invoice = $this->baseRentalInvoiceQuery($context)
            ->with([
                'customer',
                'supplier',
                'currency',
                'balance',
                'lines',
                'adjustments',
                'sources',
            ])
            ->findOrFail($invoiceId);

        $this->assertViewPermission($context, $invoice->direction instanceof InvoiceDirection ? $invoice->direction->value : (string) $invoice->direction);

        $contexts = $this->bulkLoadRentalContexts($context, collect([$invoice]));
        $rentalContext = $contexts[$invoice->id] ?? null;

        $allocations = PaymentAllocation::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->where('invoice_id', $invoice->id)
            ->with(['payment.lines'])
            ->orderByDesc('allocation_date')
            ->orderByDesc('id')
            ->get();

        $payments = $allocations->map(function (PaymentAllocation $alloc): array {
            $payment = $alloc->payment;
            $firstLine = $payment?->lines?->first();

            return [
                'id' => $alloc->id,
                'payment_id' => $alloc->payment_id,
                'payment_number' => $payment?->payment_number ?? ('#'.$alloc->payment_id),
                'payment_date' => $alloc->allocation_date?->toDateString() ?? $payment?->payment_date?->toDateString(),
                'method_name' => $firstLine?->payment_method_name_snapshot ?? 'Payment',
                'reference' => $firstLine?->reference_number ?? $firstLine?->instrument_number ?? '-',
                'allocated_amount' => (string) $alloc->allocated_amount,
                'realized_at' => $alloc->realized_at?->toIso8601String(),
                'status' => $alloc->status instanceof AllocationStatus ? $alloc->status->value : (string) $alloc->status,
                'receipt_url' => $alloc->payment_id ? "/api/v1/payments/{$alloc->payment_id}/receipt" : null,
            ];
        })->values()->all();

        $status = $invoice->status instanceof InvoiceStatus ? $invoice->status : InvoiceStatus::from((string) $invoice->status);
        $isDraft = $status === InvoiceStatus::Draft;
        $isUnposted = in_array($status, [InvoiceStatus::Draft, InvoiceStatus::Approved], true);
        $balanceDue = (string) ($invoice->balance_due ?? $invoice->balance?->remaining_amount ?? '0.000000');
        $hasOpenBalance = $this->math->compare($balanceDue, '0.000000') > 0;
        $isPosted = in_array($status, [InvoiceStatus::Posted, InvoiceStatus::PartiallyPaid], true);

        $kind = $invoice->direction === InvoiceDirection::Outbound ? AgreementKind::Customer : AgreementKind::Owner;
        $canBill = false;
        try {
            $this->authorization->assertBilling($context, $kind);
            $canBill = true;
        } catch (AuthorizationException) {
            $canBill = false;
        }

        $lines = $invoice->lines->map(fn (InvoiceLine $line): array => [
            'id' => $line->id,
            'line_number' => (int) $line->line_number,
            'description' => $line->description,
            'quantity' => (string) $line->quantity,
            'unit_price' => (string) $line->unit_price,
            'discount_amount' => (string) ($line->discount_amount ?? '0.000000'),
            'subtotal' => (string) $line->subtotal,
            'tax_total' => (string) $line->tax_total,
            'grand_total' => (string) $line->grand_total,
            'line_type' => $line->line_type instanceof InvoiceLineType ? $line->line_type->value : (string) $line->line_type,
        ])->values()->all();

        $adjustments = $invoice->adjustments->map(fn (InvoiceAdjustment $adj): array => [
            'id' => $adj->id,
            'name' => $adj->name,
            'adjustment_type' => $adj->adjustment_type instanceof AdjustmentType ? $adj->adjustment_type->value : (string) $adj->adjustment_type,
            'effect' => $adj->effect instanceof \Modules\Invoice\Enums\AdjustmentEffect ? $adj->effect->value : (string) $adj->effect,
            'amount' => (string) $adj->amount,
            'rate_snapshot' => $adj->rate_snapshot !== null ? (string) $adj->rate_snapshot : null,
        ])->values()->all();

        $cycle = BillingCycle::query()
            ->where('tenant_id', $context->tenantId)
            ->where(function (Builder $b) use ($invoice): void {
                $b->where('initial_invoice_id', $invoice->id)
                    ->orWhere('supplementary_invoice_id', $invoice->id)
                    ->orWhere('final_invoice_id', $invoice->id);
            })
            ->with(['charts.runningChart', 'charts.vehicle', 'items'])
            ->first();

        if ($cycle === null) {
            foreach ($invoice->sources as $source) {
                if (str_starts_with((string) $source->source_type, 'vehicle_rental_billing_cycle')) {
                    $cycle = BillingCycle::query()
                        ->where('tenant_id', $context->tenantId)
                        ->where('id', (int) $source->source_id)
                        ->with(['charts.runningChart', 'charts.vehicle', 'items'])
                        ->first();
                    if ($cycle !== null) {
                        break;
                    }
                }
            }
        }

        $telematics = null;
        if ($cycle !== null && $cycle->charts->isNotEmpty()) {
            $totKm = '0.000000';
            $normMin = 0;
            $doubMin = 0;
            $tripMin = 0;
            $nightOuts = 0;
            $teleVehicles = [];
            $teleDrivers = [];

            foreach ($cycle->charts as $c) {
                $totKm = $this->math->add($totKm, (string) ($c->commercial_km ?? '0.000000'), 6);
                $normMin += (int) ($c->normal_ot_minutes ?? 0);
                $doubMin += (int) ($c->double_ot_minutes ?? 0);
                $tripMin += (int) ($c->triple_ot_minutes ?? 0);
                $nightOuts += (int) ($c->night_outs ?? 0);
                $vNo = $c->vehicle?->registration_number ?? $c->vehicle?->vehicle_number;
                if ($vNo) {
                    $teleVehicles[] = $vNo;
                }
                if ($c->driver_name_snapshot) {
                    $teleDrivers[] = $c->driver_name_snapshot;
                }
            }

            $telematics = [
                'has_charts' => true,
                'trips_count' => $cycle->charts->count(),
                'commercial_km' => $totKm,
                'normal_ot_hours' => $this->math->div((string) $normMin, '60', 2),
                'double_ot_hours' => $this->math->div((string) $doubMin, '60', 2),
                'triple_ot_hours' => $this->math->div((string) $tripMin, '60', 2),
                'night_outs' => $nightOuts,
                'vehicles' => array_values(array_unique($teleVehicles)),
                'drivers' => array_values(array_unique($teleDrivers)),
            ];
        }

        $whtAmount = '0.000000';
        $maintenanceAmount = '0.000000';
        foreach ($invoice->adjustments as $adj) {
            $adjType = $adj->adjustment_type instanceof AdjustmentType ? $adj->adjustment_type->value : (string) $adj->adjustment_type;
            if ($adjType === AdjustmentType::Withholding->value || $adjType === 'withholding') {
                $whtAmount = $this->math->add($whtAmount, (string) $adj->amount);
            } elseif ($adjType === AdjustmentType::Discount->value || $adjType === 'discount') {
                $maintenanceAmount = $this->math->add($maintenanceAmount, (string) $adj->amount);
            }
        }

        $financialBreakdown = [
            'base_rent_amount' => (string) ($cycle->base_rent_amount ?? $invoice->subtotal),
            'excess_km_amount' => (string) ($cycle->excess_km_amount ?? '0.000000'),
            'overtime_amount' => (string) ($cycle->overtime_amount ?? '0.000000'),
            'night_out_amount' => (string) ($cycle->night_out_amount ?? '0.000000'),
            'tolls_amount' => (string) ($cycle->tolls_amount ?? '0.000000'),
            'additions_amount' => (string) ($cycle->additions_amount ?? '0.000000'),
            'deductions_amount' => (string) ($cycle->deductions_amount ?? $invoice->discount_total ?? '0.000000'),
            'net_subtotal' => (string) $invoice->subtotal,
            'tax_amount' => (string) $invoice->tax_total,
            'wht_withheld' => $whtAmount,
            'maintenance_deductions' => $maintenanceAmount,
            'grand_total' => (string) $invoice->grand_total,
            'paid_total' => (string) ($invoice->paid_total ?? $invoice->balance?->paid_amount ?? '0.000000'),
            'balance_due' => $balanceDue,
        ];

        $baseItem = $this->formatDocumentItem($invoice, $rentalContext);

        return array_merge($baseItem, [
            'lines' => $lines,
            'adjustments' => $adjustments,
            'payments' => $payments,
            'telematics' => $telematics,
            'financial_breakdown' => $financialBreakdown,
            'statement_url' => "/api/v1/vehicle-rental/billing/documents/{$invoice->id}/statement",
            'print_url' => "/api/v1/invoices/{$invoice->id}/print",
            'pdf_url' => "/api/v1/invoices/{$invoice->id}/pdf",
            'notes' => $invoice->notes,
            'cancellation_reason' => $invoice->cancellation_reason,
            'can_cancel' => $isUnposted && $canBill,
            'can_edit' => $isDraft && $canBill,
            'can_record_payment' => $isPosted && $hasOpenBalance && $canBill,
            'can_print' => true,
        ]);
    }

    public function getStatementData(AgreementContext $context, int $invoiceId): array
    {
        $invoice = $this->baseRentalInvoiceQuery($context)
            ->with([
                'customer',
                'supplier',
                'currency',
                'balance',
                'lines',
                'adjustments',
                'sources',
            ])
            ->findOrFail($invoiceId);

        $this->assertViewPermission($context, $invoice->direction instanceof InvoiceDirection ? $invoice->direction->value : (string) $invoice->direction);

        $cycle = BillingCycle::query()
            ->where('tenant_id', $context->tenantId)
            ->where(function (Builder $b) use ($invoice): void {
                $b->where('initial_invoice_id', $invoice->id)
                    ->orWhere('supplementary_invoice_id', $invoice->id)
                    ->orWhere('final_invoice_id', $invoice->id);
            })
            ->first();

        if ($cycle === null) {
            foreach ($invoice->sources as $source) {
                if (str_starts_with((string) $source->source_type, 'vehicle_rental_billing_cycle')) {
                    $cycle = BillingCycle::query()
                        ->where('tenant_id', $context->tenantId)
                        ->where('id', (int) $source->source_id)
                        ->first();
                    if ($cycle !== null) {
                        break;
                    }
                }
            }
        }

        if ($cycle !== null) {
            return app(TwoStageInvoicingService::class)->getCycleStatementData($context, $cycle->id);
        }

        $contexts = $this->bulkLoadRentalContexts($context, collect([$invoice]));
        $rentalContext = $contexts[$invoice->id] ?? [];
        $agreement = $rentalContext['agreement'] ?? null;
        $vehicle = $rentalContext['vehicle'] ?? null;
        $party = $invoice->direction === InvoiceDirection::Outbound ? $invoice->customer : $invoice->supplier;
        $currencyCode = $invoice->currency?->code ?? 'LKR';

        return [
            'header' => [
                'company_name' => 'AutoERP Vehicle Rental Operations',
                'report_title' => $invoice->direction === InvoiceDirection::Outbound ? 'Customer Rental Billing Statement' : 'Lessor Settlement Financial Statement',
                'cycle_number' => $invoice->invoice_number,
                'as_of_date' => now()->toDateString(),
                'status' => strtoupper($invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status),
                'mode' => 'Direct Settlement',
                'finalization_mode' => 'Direct',
            ],
            'agreement' => [
                'id' => $agreement['id'] ?? null,
                'reference' => $agreement['reference'] ?? '—',
                'starts_on' => $agreement['starts_on'] ?? '—',
                'ends_on' => $agreement['ends_on'] ?? 'Open-ended',
                'period_from' => $invoice->supply_period_start?->format('d/m/Y') ?? $invoice->invoice_date?->format('d/m/Y'),
                'period_until' => $invoice->supply_period_end?->format('d/m/Y') ?? $invoice->invoice_date?->format('d/m/Y'),
                'currency' => $currencyCode,
            ],
            'party' => [
                'name' => $party?->display_name ?? $party?->name ?? 'Commercial Client',
                'code' => $party?->code ?? $party?->customer_number ?? $party?->supplier_number ?? '—',
                'phone' => $party?->phone ?? $party?->mobile ?? '—',
                'address' => $party?->billing_address ?? $party?->address ?? '—',
            ],
            'financials' => [
                'base_rent_amount' => (string) $invoice->subtotal,
                'excess_km_amount' => '0.000000',
                'overtime_amount' => '0.000000',
                'night_out_amount' => '0.000000',
                'tolls_amount' => '0.000000',
                'additions_amount' => '0.000000',
                'deductions_amount' => (string) $invoice->discount_total,
                'net_subtotal' => (string) $invoice->subtotal,
                'tax_amount' => (string) $invoice->tax_total,
                'total_billed_amount' => (string) $invoice->grand_total,
                'total_paid_amount' => (string) ($invoice->paid_total ?? $invoice->balance?->paid_amount ?? '0.000000'),
                'balance_due' => (string) ($invoice->balance_due ?? $invoice->balance?->remaining_amount ?? '0.000000'),
            ],
            'invoices' => [
                'initial' => [
                    'number' => $invoice->invoice_number,
                    'date' => $invoice->invoice_date?->format('d/m/Y') ?? '—',
                    'total' => (string) $invoice->grand_total,
                    'paid' => (string) ($invoice->paid_total ?? $invoice->balance?->paid_amount ?? '0.000000'),
                    'status' => $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status,
                ],
                'supplementary' => null,
                'final' => null,
            ],
            'vehicle_attributions' => $vehicle ? [
                [
                    'vehicle_id' => $vehicle['id'] ?? 0,
                    'vehicle_number' => $vehicle['registration_number'] ?? '—',
                    'vehicle_label' => $vehicle['make_model'] ?? '—',
                    'is_replacement' => false,
                    'charts_count' => 0,
                    'custody_days' => 30,
                    'total_km' => '0.000000',
                    'normal_ot_hours' => '0.000000',
                    'double_ot_hours' => '0.000000',
                    'triple_ot_hours' => '0.000000',
                    'night_outs' => 0,
                    'allocated_base_rent' => (string) $invoice->subtotal,
                    'allocated_excess_km_amount' => '0.000000',
                    'allocated_tolls_amount' => '0.000000',
                    'allocated_ot_amount' => '0.000000',
                    'allocated_night_out_amount' => '0.000000',
                    'attributed_additions' => '0.000000',
                    'attributed_deductions' => (string) $invoice->discount_total,
                    'subtotal' => (string) $invoice->grand_total,
                ],
            ] : [],
            'driver_attributions' => [],
            'charts' => [],
            'items' => $invoice->lines->map(fn (InvoiceLine $l) => [
                'id' => $l->id,
                'item_type' => 'charge',
                'category' => 'rental_service',
                'description' => $l->description,
                'quantity' => (string) $l->quantity,
                'unit_rate' => (string) $l->unit_price,
                'amount' => (string) $l->grand_total,
                'vehicle_id' => null,
                'vehicle_number' => null,
                'driver_employee_id' => null,
                'driver_name' => null,
            ])->all(),
            'reassignments' => [],
        ];
    }

    public function updateDocument(AgreementContext $context, int $invoiceId, array $data): array
    {
        return DB::transaction(function () use ($context, $invoiceId, $data): array {
            $invoice = Invoice::query()
                ->where('tenant_id', $context->tenantId)
                ->where('organization_unit_id', $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            $this->statusService->assertEditable($invoice);

            $expectedVersion = (int) ($data['expected_version'] ?? 0);
            if ($expectedVersion !== (int) $invoice->row_version) {
                throw new InvalidArgumentException('Invoice was changed by another request. Reload it before performing this action.');
            }

            $kind = $invoice->direction === InvoiceDirection::Outbound ? AgreementKind::Customer : AgreementKind::Owner;
            $this->authorization->assertBilling($context, $kind);

            if (array_key_exists('due_date', $data)) {
                $invoice->due_date = $data['due_date'];
            }
            if (array_key_exists('notes', $data)) {
                $invoice->notes = $data['notes'];
            }

            $invoice->save();

            return $this->getDocumentDetail($context, $invoice->id);
        });
    }

    public function cancelDocument(AgreementContext $context, int $invoiceId, int $expectedVersion, string $reason): array
    {
        return DB::transaction(function () use ($context, $invoiceId, $expectedVersion, $reason): array {
            $invoice = Invoice::query()
                ->where('tenant_id', $context->tenantId)
                ->where('organization_unit_id', $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            $kind = $invoice->direction === InvoiceDirection::Outbound ? AgreementKind::Customer : AgreementKind::Owner;
            $this->authorization->assertBilling($context, $kind);

            $this->statusService->transitionIfVersion(
                invoice: $invoice,
                to: InvoiceStatus::Cancelled,
                expectedVersion: $expectedVersion,
                actorId: $context->actorId,
                reason: $reason,
            );

            $refreshed = $invoice->fresh(['balance', 'sources']);
            if ($refreshed->balance !== null) {
                $this->balanceService->cancel($refreshed);
            }

            // Restore / unblock underlying rental sources
            $this->restoreSourcesOnCancellation($context, $refreshed, $reason);

            return $this->getDocumentDetail($context, $invoice->id);
        });
    }

    private function restoreSourcesOnCancellation(AgreementContext $context, Invoice $invoice, string $reason): void
    {
        // 1. Unfinalized Billing Cycles directly referencing this invoice
        $cycles = BillingCycle::query()
            ->where('tenant_id', $context->tenantId)
            ->where(function (Builder $b) use ($invoice): void {
                $b->where('initial_invoice_id', $invoice->id)
                    ->orWhere('supplementary_invoice_id', $invoice->id)
                    ->orWhere('final_invoice_id', $invoice->id);
            })
            ->where('status', '!=', BillingCycleStatus::Cancelled)
            ->lockForUpdate()
            ->get();

        $handledCycleIds = [];
        foreach ($cycles as $cycle) {
            $handledCycleIds[] = $cycle->id;
            if ($cycle->supplementary_invoice_id === $invoice->id) {
                $cycle->supplementary_invoice_id = null;
                $cycle->status = BillingCycleStatus::Stage2Attached;
                $cycle->finalization_mode = BillingFinalizationMode::SupplementaryInvoice;
                $cycle->finalized_at = null;
                $cycle->finalized_by = null;
                if ($cycle->initial_invoice_id !== null && $cycle->initialInvoice) {
                    $cycle->total_billed_amount = (string) $cycle->initialInvoice->grand_total;
                    $cycle->tax_amount = (string) $cycle->initialInvoice->tax_total;
                } else {
                    $cycle->total_billed_amount = '0.000000';
                    $cycle->tax_amount = '0.000000';
                }
                $cycle->notes = trim(($cycle->notes ?? '')."\n[Supplementary Cancelled]: Invoice {$invoice->invoice_number} cancelled: {$reason}");
            } elseif ($cycle->owner_agreement_id !== null && $cycle->initial_invoice_id === null && $cycle->final_invoice_id === $invoice->id) {
                $cycle->final_invoice_id = null;
                $cycle->finalization_mode = BillingFinalizationMode::SupplementaryInvoice;
                $cycle->finalized_at = null;
                $cycle->finalized_by = null;
                $cycle->status = $cycle->charts()->exists() ? BillingCycleStatus::Stage2Attached : BillingCycleStatus::Draft;
                $cycle->notes = trim(($cycle->notes ?? '')."\n[Owner Settlement Cancelled]: Settlement bill {$invoice->invoice_number} cancelled: {$reason}");
            } else {
                $cycle->status = BillingCycleStatus::Cancelled;
                $cycle->notes = trim(($cycle->notes ?? '')."\n[Cancelled]: Invoice {$invoice->invoice_number} cancelled: {$reason}");
            }
            $cycle->row_version += 1;
            $cycle->save();
        }

        // 2. Unlink any rental expense allocations linked to this invoice
        $allocations = RentalExpenseAllocation::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $invoice->id)
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $alloc) {
            $alloc->invoice_id = null;
            $alloc->status = RentalExpenseAllocationStatus::Confirmed;
            $alloc->row_version += 1;
            $alloc->save();
        }

        // 2b. Unlink any agreement adjustments linked to this invoice
        $adjustments = AgreementAdjustment::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $invoice->id)
            ->lockForUpdate()
            ->get();

        foreach ($adjustments as $adj) {
            $fromStatus = $adj->status;
            $adj->invoice_id = null;
            $adj->status = AgreementAdjustmentStatus::Authorized;
            $adj->row_version += 1;
            $adj->save();

            $history = new AgreementAdjustmentHistory();
            $history->tenant_id = $context->tenantId;
            $history->adjustment_id = $adj->id;
            $history->from_status = $fromStatus;
            $history->to_status = AgreementAdjustmentStatus::Authorized;
            $history->reason = "Restored on invoice {$invoice->invoice_number} cancellation: {$reason}";
            $history->user_id = $context->actorId;
            $history->save();
        }

        // 3. Void underlying base & usage charges if unposted invoice was cancelled
        foreach ($invoice->sources as $source) {
            $st = (string) $source->source_type;
            $sid = (int) $source->source_id;

            if ($st === BaseChargeSource::Customer->value) {
                $charge = CustomerBaseCharge::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('id', $sid)
                    ->whereNull('voided_at')
                    ->lockForUpdate()
                    ->first();
                if ($charge) {
                    $charge->forceFill([
                        'voided_at' => now(),
                        'voided_by' => $context->actorId,
                        'void_reason' => $reason,
                        'row_version' => $charge->row_version + 1,
                    ])->save();
                }
            } elseif ($st === BaseChargeSource::Owner->value) {
                $charge = OwnerBaseCharge::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('id', $sid)
                    ->whereNull('voided_at')
                    ->lockForUpdate()
                    ->first();
                if ($charge) {
                    $charge->forceFill([
                        'voided_at' => now(),
                        'voided_by' => $context->actorId,
                        'void_reason' => $reason,
                        'row_version' => $charge->row_version + 1,
                    ])->save();
                }
            } elseif ($st === UsageChargeSource::Customer->value) {
                $charge = CustomerUsageCharge::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('id', $sid)
                    ->whereNull('voided_at')
                    ->lockForUpdate()
                    ->first();
                if ($charge) {
                    $charge->forceFill([
                        'voided_at' => now(),
                        'voided_by' => $context->actorId,
                        'void_reason' => $reason,
                        'row_version' => $charge->row_version + 1,
                    ])->save();
                }
            } elseif ($st === UsageChargeSource::Owner->value) {
                $charge = OwnerUsageCharge::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('id', $sid)
                    ->whereNull('voided_at')
                    ->lockForUpdate()
                    ->first();
                if ($charge) {
                    $charge->forceFill([
                        'voided_at' => now(),
                        'voided_by' => $context->actorId,
                        'void_reason' => $reason,
                        'row_version' => $charge->row_version + 1,
                    ])->save();
                }
            } elseif (str_starts_with($st, 'vehicle_rental_billing_cycle')) {
                if (in_array($sid, $handledCycleIds, true)) {
                    continue;
                }
                $cycle = BillingCycle::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('id', $sid)
                    ->where('status', '!=', BillingCycleStatus::Cancelled)
                    ->lockForUpdate()
                    ->first();
                if ($cycle) {
                    if ($cycle->supplementary_invoice_id === $invoice->id) {
                        $cycle->supplementary_invoice_id = null;
                        $cycle->status = BillingCycleStatus::Stage2Attached;
                        $cycle->finalization_mode = BillingFinalizationMode::SupplementaryInvoice;
                        $cycle->finalized_at = null;
                        $cycle->finalized_by = null;
                        if ($cycle->initial_invoice_id !== null && $cycle->initialInvoice) {
                            $cycle->total_billed_amount = (string) $cycle->initialInvoice->grand_total;
                            $cycle->tax_amount = (string) $cycle->initialInvoice->tax_total;
                        } else {
                            $cycle->total_billed_amount = '0.000000';
                            $cycle->tax_amount = '0.000000';
                        }
                        $cycle->notes = trim(($cycle->notes ?? '')."\n[Supplementary Cancelled]: Linked invoice {$invoice->invoice_number} cancelled: {$reason}");
                    } elseif ($cycle->owner_agreement_id !== null && $cycle->initial_invoice_id === null && $cycle->final_invoice_id === $invoice->id) {
                        $cycle->final_invoice_id = null;
                        $cycle->finalization_mode = BillingFinalizationMode::SupplementaryInvoice;
                        $cycle->finalized_at = null;
                        $cycle->finalized_by = null;
                        $cycle->status = $cycle->charts()->exists() ? BillingCycleStatus::Stage2Attached : BillingCycleStatus::Draft;
                        $cycle->notes = trim(($cycle->notes ?? '')."\n[Owner Settlement Cancelled]: Linked invoice {$invoice->invoice_number} cancelled: {$reason}");
                    } else {
                        $cycle->status = BillingCycleStatus::Cancelled;
                        $cycle->notes = trim(($cycle->notes ?? '')."\n[Cancelled]: Linked invoice {$invoice->invoice_number} cancelled: {$reason}");
                    }
                    $cycle->row_version += 1;
                    $cycle->save();
                }
            }
        }
    }

    private function assertViewPermission(AgreementContext $context, ?string $direction): void
    {
        $canCustomer = false;
        $canOwner = false;

        try {
            $this->authorization->assert($context, AgreementKind::Customer, false);
            $canCustomer = true;
        } catch (AuthorizationException) {
            $canCustomer = false;
        }

        try {
            $this->authorization->assert($context, AgreementKind::Owner, false);
            $canOwner = true;
        } catch (AuthorizationException) {
            $canOwner = false;
        }

        if ($direction === InvoiceDirection::Outbound->value || $direction === 'outbound') {
            if (! $canCustomer) {
                throw new AuthorizationException('This Rental action requires permission: '.RentalAuthorization::CUSTOMER_VIEW);
            }
        } elseif ($direction === InvoiceDirection::Inbound->value || $direction === 'inbound') {
            if (! $canOwner) {
                throw new AuthorizationException('This Rental action requires permission: '.RentalAuthorization::OWNER_VIEW);
            }
        } else {
            if (! $canCustomer && ! $canOwner) {
                throw new AuthorizationException('This Rental action requires permission: '.RentalAuthorization::CUSTOMER_VIEW);
            }
        }
    }

    private function formatDocumentItem(Invoice $invoice, ?array $context): array
    {
        $today = CarbonImmutable::now()->toDateString();
        $balanceDue = (string) ($invoice->balance_due ?? $invoice->balance?->remaining_amount ?? '0.000000');
        $paidTotal = (string) ($invoice->paid_total ?? $invoice->balance?->paid_amount ?? '0.000000');
        $grandTotal = (string) $invoice->grand_total;

        $status = $invoice->status instanceof InvoiceStatus ? $invoice->status : InvoiceStatus::from((string) $invoice->status);
        $direction = $invoice->direction instanceof InvoiceDirection ? $invoice->direction : InvoiceDirection::from((string) $invoice->direction);

        if ($status === InvoiceStatus::Cancelled || $status === InvoiceStatus::Void) {
            $balanceStatus = 'cancelled';
        } elseif ($this->math->compare($balanceDue, '0.000000') <= 0 && $this->math->compare($grandTotal, '0.000000') > 0) {
            $balanceStatus = 'paid';
        } elseif ($invoice->due_date !== null && $invoice->due_date->toDateString() < $today && $this->math->compare($balanceDue, '0.000000') > 0) {
            $balanceStatus = 'overdue';
        } elseif ($this->math->compare($paidTotal, '0.000000') > 0) {
            $balanceStatus = 'partially_paid';
        } else {
            $balanceStatus = 'unpaid';
        }

        $whtAmount = '0.000000';
        if ($invoice->relationLoaded('adjustments')) {
            foreach ($invoice->adjustments as $adj) {
                $adjType = $adj->adjustment_type instanceof AdjustmentType ? $adj->adjustment_type->value : (string) $adj->adjustment_type;
                if ($adjType === AdjustmentType::Withholding->value || $adjType === 'withholding') {
                    $whtAmount = $this->math->add($whtAmount, (string) $adj->amount);
                }
            }
        }

        $grossAmount = (string) $invoice->subtotal;

        $partyName = '-';
        $partyCode = '-';
        $partyPhone = null;
        if ($direction === InvoiceDirection::Outbound && $invoice->customer) {
            $partyName = $invoice->customer->name;
            $partyCode = $invoice->customer->code ?? $invoice->customer->customer_number ?? '-';
            $partyPhone = $invoice->customer->phone ?? $invoice->customer->mobile;
        } elseif ($direction === InvoiceDirection::Inbound && $invoice->supplier) {
            $partyName = $invoice->supplier->name;
            $partyCode = $invoice->supplier->code ?? $invoice->supplier->supplier_number ?? '-';
            $partyPhone = $invoice->supplier->phone ?? $invoice->supplier->mobile;
        }

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_type' => $invoice->invoice_type instanceof InvoiceType ? $invoice->invoice_type->value : (string) $invoice->invoice_type,
            'direction' => $direction->value,
            'status' => $status->value,
            'balance_status' => $balanceStatus,
            'invoice_date' => $invoice->invoice_date?->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'currency' => [
                'id' => $invoice->currency_id,
                'code' => $invoice->currency?->code ?? 'LKR',
                'symbol' => $invoice->currency?->symbol ?? 'Rs',
            ],
            'subtotal' => (string) $invoice->subtotal,
            'tax_total' => (string) $invoice->tax_total,
            'discount_total' => (string) $invoice->discount_total,
            'grand_total' => $grandTotal,
            'paid_total' => $paidTotal,
            'credit_total' => (string) ($invoice->credit_total ?? $invoice->balance?->credit_allocated_amount ?? '0.000000'),
            'balance_due' => $balanceDue,
            'wht_amount' => $whtAmount,
            'gross_amount' => $grossAmount,
            'party' => [
                'id' => $invoice->party_id,
                'type' => $invoice->party_type,
                'name' => $partyName,
                'code' => $partyCode,
                'phone' => $partyPhone,
            ],
            'agreement' => $context['agreement'] ?? null,
            'vehicle' => $context['vehicle'] ?? null,
            'driver_name' => $context['driver_name'] ?? null,
            'row_version' => (int) $invoice->row_version,
            'created_at' => $invoice->created_at?->toIso8601String(),
        ];
    }

    /**
     * Bulk resolves agreement, vehicle, and driver details for invoices without N+1 queries.
     *
     * @param Collection<int, Invoice> $invoices
     * @return array<int, array{agreement: ?array, vehicle: ?array, driver_name: ?string}>
     */
    private function bulkLoadRentalContexts(AgreementContext $context, Collection $invoices): array
    {
        if ($invoices->isEmpty()) {
            return [];
        }

        $invoiceIds = $invoices->pluck('id')->all();

        // 1. Direct billing cycles linked to these invoices
        $billingCycles = BillingCycle::query()
            ->where('tenant_id', $context->tenantId)
            ->where(function (Builder $b) use ($invoiceIds): void {
                $b->whereIn('initial_invoice_id', $invoiceIds)
                    ->orWhereIn('supplementary_invoice_id', $invoiceIds)
                    ->orWhereIn('final_invoice_id', $invoiceIds);
            })
            ->with([
                'customerAgreement.party',
                'customerAgreement.vehicleUses.vehicle.make',
                'customerAgreement.vehicleUses.vehicle.model',
                'ownerAgreement.party',
                'ownerAgreement.vehicleUses.vehicle.make',
                'ownerAgreement.vehicleUses.vehicle.model',
                'items.vehicle.make',
                'items.vehicle.model',
                'items.driver',
                'charts.vehicle',
            ])
            ->get();

        $invoiceToCycle = [];
        foreach ($billingCycles as $cycle) {
            if ($cycle->initial_invoice_id) {
                $invoiceToCycle[$cycle->initial_invoice_id] = $cycle;
            }
            if ($cycle->supplementary_invoice_id) {
                $invoiceToCycle[$cycle->supplementary_invoice_id] = $cycle;
            }
            if ($cycle->final_invoice_id) {
                $invoiceToCycle[$cycle->final_invoice_id] = $cycle;
            }
        }

        // 2. Sources linked to cycles or agreements
        $cycleIds = [];
        $ownerAgreementIds = [];
        $customerBaseChargeIds = [];
        $ownerBaseChargeIds = [];
        $customerUsageChargeIds = [];
        $ownerUsageChargeIds = [];
        $agreementReferences = [];

        foreach ($invoices as $inv) {
            foreach ($inv->sources as $source) {
                $st = (string) $source->source_type;
                $sid = (int) $source->source_id;
                if (str_starts_with($st, 'vehicle_rental_billing_cycle')) {
                    $cycleIds[] = $sid;
                } elseif ($st === 'vehicle_rental_owner_settlement') {
                    $ownerAgreementIds[] = $sid;
                } elseif ($st === BaseChargeSource::Customer->value) {
                    $customerBaseChargeIds[] = $sid;
                } elseif ($st === BaseChargeSource::Owner->value) {
                    $ownerBaseChargeIds[] = $sid;
                } elseif ($st === UsageChargeSource::Customer->value) {
                    $customerUsageChargeIds[] = $sid;
                } elseif ($st === UsageChargeSource::Owner->value) {
                    $ownerUsageChargeIds[] = $sid;
                }

                if ($source->source_document_number) {
                    $parts = explode('-', $source->source_document_number);
                    if (count($parts) >= 3) {
                        $agreementReferences[] = $parts[0].'-'.$parts[1].'-'.$parts[2];
                    }
                    $agreementReferences[] = $source->source_document_number;
                }
            }
        }

        if (! empty($cycleIds)) {
            $extraCycles = BillingCycle::query()
                ->where('tenant_id', $context->tenantId)
                ->whereIn('id', array_unique($cycleIds))
                ->with([
                    'customerAgreement.party',
                    'customerAgreement.vehicleUses.vehicle.make',
                    'customerAgreement.vehicleUses.vehicle.model',
                    'ownerAgreement.party',
                    'ownerAgreement.vehicleUses.vehicle.make',
                    'ownerAgreement.vehicleUses.vehicle.model',
                    'items.vehicle.make',
                    'items.vehicle.model',
                    'items.driver',
                    'charts.vehicle',
                ])
                ->get()
                ->keyBy('id');
        } else {
            $extraCycles = collect();
        }

        // Base & Usage charges mapping to agreement id
        $customerAgrIdsFromCharges = [];
        if (! empty($customerBaseChargeIds)) {
            $customerAgrIdsFromCharges = array_merge(
                $customerAgrIdsFromCharges,
                CustomerBaseCharge::query()->where('tenant_id', $context->tenantId)->whereIn('id', array_unique($customerBaseChargeIds))->pluck('agreement_id')->all()
            );
        }
        if (! empty($customerUsageChargeIds)) {
            $customerAgrIdsFromCharges = array_merge(
                $customerAgrIdsFromCharges,
                CustomerUsageCharge::query()->where('tenant_id', $context->tenantId)->whereIn('id', array_unique($customerUsageChargeIds))->pluck('agreement_id')->all()
            );
        }

        $ownerAgrIdsFromCharges = [];
        if (! empty($ownerBaseChargeIds)) {
            $ownerAgrIdsFromCharges = array_merge(
                $ownerAgrIdsFromCharges,
                OwnerBaseCharge::query()->where('tenant_id', $context->tenantId)->whereIn('id', array_unique($ownerBaseChargeIds))->pluck('agreement_id')->all()
            );
        }
        if (! empty($ownerUsageChargeIds)) {
            $ownerAgrIdsFromCharges = array_merge(
                $ownerAgrIdsFromCharges,
                OwnerUsageCharge::query()->where('tenant_id', $context->tenantId)->whereIn('id', array_unique($ownerUsageChargeIds))->pluck('agreement_id')->all()
            );
        }

        $allCustomerAgrIds = array_unique(array_filter($customerAgrIdsFromCharges));
        $allOwnerAgrIds = array_unique(array_filter(array_merge($ownerAgreementIds, $ownerAgrIdsFromCharges)));
        $allAgreementRefs = array_unique(array_filter($agreementReferences));

        $customerAgreements = (! empty($allCustomerAgrIds) || ! empty($allAgreementRefs))
            ? CustomerAgreement::query()
                ->where('tenant_id', $context->tenantId)
                ->where(function (Builder $b) use ($allCustomerAgrIds, $allAgreementRefs): void {
                    if (! empty($allCustomerAgrIds)) {
                        $b->whereIn('id', $allCustomerAgrIds);
                    }
                    if (! empty($allAgreementRefs)) {
                        $b->orWhereIn('reference', $allAgreementRefs);
                    }
                })
                ->with(['party', 'vehicleUses.vehicle.make', 'vehicleUses.vehicle.model'])
                ->get()
            : collect();

        $ownerAgreements = (! empty($allOwnerAgrIds) || ! empty($allAgreementRefs))
            ? OwnerAgreement::query()
                ->where('tenant_id', $context->tenantId)
                ->where(function (Builder $b) use ($allOwnerAgrIds, $allAgreementRefs): void {
                    if (! empty($allOwnerAgrIds)) {
                        $b->whereIn('id', $allOwnerAgrIds);
                    }
                    if (! empty($allAgreementRefs)) {
                        $b->orWhereIn('reference', $allAgreementRefs);
                    }
                })
                ->with(['party', 'vehicleUses.vehicle.make', 'vehicleUses.vehicle.model'])
                ->get()
            : collect();

        $custAgrById = $customerAgreements->keyBy('id');
        $custAgrByRef = $customerAgreements->keyBy('reference');
        $ownAgrById = $ownerAgreements->keyBy('id');
        $ownAgrByRef = $ownerAgreements->keyBy('reference');

        $result = [];
        foreach ($invoices as $inv) {
            $agr = null;
            $veh = null;
            $drvName = null;
            $kind = $inv->direction === InvoiceDirection::Outbound ? 'customer' : 'owner';

            // Check if from direct cycle
            $cycle = $invoiceToCycle[$inv->id] ?? null;

            // Check from sources if not direct
            if ($cycle === null) {
                foreach ($inv->sources as $source) {
                    if (str_starts_with((string) $source->source_type, 'vehicle_rental_billing_cycle')) {
                        $cycle = $extraCycles->get((int) $source->source_id);
                        if ($cycle !== null) {
                            break;
                        }
                    }
                }
            }

            if ($cycle !== null) {
                if ($kind === 'customer' && $cycle->customerAgreement) {
                    $agr = $cycle->customerAgreement;
                } elseif ($cycle->ownerAgreement) {
                    $agr = $cycle->ownerAgreement;
                }

                $firstItem = $cycle->items->first();
                if ($firstItem?->vehicle) {
                    $veh = $firstItem->vehicle;
                }
                if ($firstItem?->driver) {
                    $drvName = $firstItem->driver->display_name ?? trim($firstItem->driver->first_name.' '.$firstItem->driver->last_name);
                }

                if (empty($drvName) && $cycle->relationLoaded('charts')) {
                    $chartDrivers = $cycle->charts->pluck('driver_name_snapshot')->filter()->unique()->values();
                    if ($chartDrivers->isNotEmpty()) {
                        $drvName = $chartDrivers->join(', ');
                    }
                }
                if ($veh === null && $cycle->relationLoaded('charts') && $cycle->charts->isNotEmpty()) {
                    $veh = $cycle->charts->first()?->vehicle;
                }
            }

            // If not found via cycle, look via sources or document numbers
            if ($agr === null) {
                foreach ($inv->sources as $source) {
                    $st = (string) $source->source_type;
                    $sid = (int) $source->source_id;

                    if ($st === 'vehicle_rental_owner_settlement') {
                        $agr = $ownAgrById->get($sid);
                    } elseif ($st === BaseChargeSource::Customer->value || $st === UsageChargeSource::Customer->value) {
                        $agr = $custAgrById->get($customerAgrIdsFromCharges[$sid] ?? null);
                    } elseif ($st === BaseChargeSource::Owner->value || $st === UsageChargeSource::Owner->value) {
                        $agr = $ownAgrById->get($ownerAgrIdsFromCharges[$sid] ?? null);
                    }

                    if ($agr === null && $source->source_document_number) {
                        $docNum = $source->source_document_number;
                        $parts = explode('-', $docNum);
                        $prefixRef = count($parts) >= 3 ? $parts[0].'-'.$parts[1].'-'.$parts[2] : $docNum;
                        $agr = $kind === 'customer'
                            ? ($custAgrByRef->get($docNum) ?? $custAgrByRef->get($prefixRef))
                            : ($ownAgrByRef->get($docNum) ?? $ownAgrByRef->get($prefixRef));
                    }

                    if ($agr !== null) {
                        break;
                    }
                }
            }

            // Fallback vehicle from agreement vehicle uses
            if ($veh === null && $agr !== null && $agr->relationLoaded('vehicleUses')) {
                $veh = $agr->vehicleUses->first()?->vehicle;
            }

            $sourceDocNum = $inv->sources->first()?->source_document_number;

            $result[$inv->id] = [
                'agreement' => $agr !== null ? [
                    'id' => $agr->id,
                    'reference' => $agr->reference,
                    'kind' => $agr instanceof CustomerAgreement ? 'customer' : 'owner',
                    'status' => $agr->status?->value ?? (string) $agr->status,
                    'starts_on' => $agr->starts_on?->format('Y-m-d'),
                    'ends_on' => $agr->ends_on?->format('Y-m-d'),
                ] : ($sourceDocNum ? [
                    'id' => null,
                    'reference' => $sourceDocNum,
                    'kind' => $kind,
                    'status' => null,
                    'starts_on' => null,
                    'ends_on' => null,
                ] : null),
                'vehicle' => $veh !== null ? [
                    'id' => $veh->id,
                    'registration_number' => $veh->registration_number,
                    'make_model' => trim(($veh->make?->name ?? '').' '.($veh->model?->name ?? '')),
                ] : null,
                'driver_name' => $drvName,
            ];
        }

        return $result;
    }
}
