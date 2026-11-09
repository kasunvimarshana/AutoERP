<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Tenant\Models\TenantModel;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\BaseChargeSource;
use Modules\VehicleRental\Enums\UsageChargeSource;
use Modules\VehicleRental\Models\CustomerAgreement;

final class LesseeOutstandingReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{as_of_date?: ?string, customer_id?: ?int, agreement_id?: ?int, aging_bucket?: ?string}  $params
     * @return array<string, mixed>
     */
    public function generate(AgreementContext $context, array $params): array
    {
        $this->authorization->assertReport($context);

        $asOfDate = ! empty($params['as_of_date'])
            ? CarbonImmutable::parse($params['as_of_date'])->endOfDay()
            : CarbonImmutable::today()->endOfDay();

        $selectedCustomerId = ! empty($params['customer_id']) ? (int) $params['customer_id'] : null;
        $selectedAgreementId = ! empty($params['agreement_id']) ? (int) $params['agreement_id'] : null;
        $agingBucket = (string) ($params['aging_bucket'] ?? 'all');

        // Fetch customer agreements in tenant to cross-reference vehicles and durations
        $customerAgreements = CustomerAgreement::query()
            ->forTenant($context->tenantId)
            ->with(['vehicleUses.vehicle'])
            ->when($selectedAgreementId !== null, fn ($q) => $q->where('id', $selectedAgreementId))
            ->when($selectedCustomerId !== null, fn ($q) => $q->where('customer_id', $selectedCustomerId))
            ->get();

        $agreementsByReference = $customerAgreements->keyBy('reference');
        $agreementsByCustomer = $customerAgreements->groupBy('customer_id');

        // Query outstanding customer invoices
        $invoicesQuery = DB::table('invoices')
            ->join('invoice_balances', 'invoice_balances.invoice_id', '=', 'invoices.id')
            ->leftJoin('customers', 'customers.id', '=', 'invoices.party_id')
            ->leftJoin('invoice_document_snapshots', function ($join) {
                $join->on('invoice_document_snapshots.invoice_id', '=', 'invoices.id')
                    ->on('invoice_document_snapshots.tenant_id', '=', 'invoices.tenant_id');
            })
            ->where('invoices.tenant_id', $context->tenantId)
            ->where('invoices.party_type', 'customer')
            ->where('invoice_balances.remaining_amount', '>', 0)
            ->where('invoices.invoice_date', '<=', $asOfDate->toDateString())
            ->when($selectedCustomerId !== null, fn (Builder $q) => $q->where('invoices.party_id', $selectedCustomerId))
            ->select([
                'invoices.id as invoice_id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.grand_total',
                'invoice_balances.remaining_amount',
                'invoices.party_id as customer_id',
                'invoice_document_snapshots.supply_period_start',
                'invoice_document_snapshots.supply_period_end',
            ])
            ->selectRaw("COALESCE(NULLIF(customers.name, ''), invoices.party_name_snapshot, 'Valued Customer') as customer_name")
            ->selectRaw("COALESCE(NULLIF(customers.code, ''), invoices.party_code_snapshot, 'CUST') as customer_code")
            ->selectRaw("COALESCE(NULLIF(customers.phone, ''), invoices.party_phone_snapshot, '') as customer_phone")
            ->selectSub(
                DB::table('invoice_sources')
                    ->select('source_document_number')
                    ->whereColumn('invoice_sources.invoice_id', 'invoices.id')
                    ->whereColumn('invoice_sources.tenant_id', 'invoices.tenant_id')
                    ->where(function (Builder $sq): void {
                        $sq->whereIn('invoice_sources.source_type', [
                            BaseChargeSource::Customer->value,
                            UsageChargeSource::Customer->value,
                            'vehicle_rental_customer_recovery',
                            'base_rent',
                            'excess_mileage',
                            'overtime',
                            'extra_hours',
                            'night_out',
                        ])->orWhere('invoice_sources.source_type', 'like', 'vehicle_rental_%');
                    })
                    ->limit(1),
                'rental_agreement_number',
            )
            ->selectSub(
                DB::table('invoice_lines')
                    ->select('description')
                    ->whereColumn('invoice_lines.invoice_id', 'invoices.id')
                    ->whereColumn('invoice_lines.tenant_id', 'invoices.tenant_id')
                    ->orderBy('invoice_lines.line_number')
                    ->limit(1),
                'line_description',
            )
            ->orderBy('customers.name')
            ->orderBy('invoices.invoice_date')
            ->orderBy('invoices.invoice_number');

        $rawInvoices = $invoicesQuery->get();

        // Also fetch customer contacts to show Contact person if available
        $customerIds = $rawInvoices->pluck('customer_id')->unique()->filter()->values()->all();
        $customersWithContacts = Customer::query()
            ->whereIn('id', $customerIds)
            ->with('contacts')
            ->get()
            ->keyBy('id');

        $groups = [];
        $grandTotalOriginal = '0.00';
        $grandTotalBalance = '0.00';
        $totalInvoicesCount = 0;

        foreach ($rawInvoices as $inv) {
            $agreementRef = $inv->rental_agreement_number;
            $matchedAgreement = $agreementRef !== null ? $agreementsByReference->get($agreementRef) : null;

            // If not found by reference, try to find single active agreement for customer
            if ($matchedAgreement === null && $agreementsByCustomer->has($inv->customer_id)) {
                $matchedAgreement = $agreementsByCustomer->get($inv->customer_id)?->first();
            }

            // If filter by agreement is active and invoice does not match, skip
            if ($selectedAgreementId !== null && $matchedAgreement?->id !== $selectedAgreementId) {
                continue;
            }

            $invoiceDate = CarbonImmutable::parse($inv->invoice_date);
            $ageDays = max(0, $asOfDate->diffInDays($invoiceDate));

            // Filter aging bucket if requested
            if ($agingBucket === '0_30' && $ageDays > 30) {
                continue;
            }
            if ($agingBucket === '31_60' && ($ageDays <= 30 || $ageDays > 60)) {
                continue;
            }
            if ($agingBucket === '61_90' && ($ageDays <= 60 || $ageDays > 90)) {
                continue;
            }
            if ($agingBucket === '90_plus' && $ageDays <= 90) {
                continue;
            }

            $periodLabel = ($inv->supply_period_start && $inv->supply_period_end)
                ? CarbonImmutable::parse($inv->supply_period_start)->format('d/m/Y').' To '.CarbonImmutable::parse($inv->supply_period_end)->format('d/m/Y')
                : (($matchedAgreement?->starts_on && $matchedAgreement?->ends_on)
                    ? $matchedAgreement->starts_on->format('d/m/Y').' To '.$matchedAgreement->ends_on->format('d/m/Y')
                    : ($agreementRef ?? 'Regular Billing'));

            $vehicleNo = '-';
            if ($matchedAgreement !== null && $matchedAgreement->vehicleUses->isNotEmpty()) {
                $primaryUse = $matchedAgreement->vehicleUses->firstWhere('replaces_use_id', null) ?? $matchedAgreement->vehicleUses->first();
                $vehicleNo = $primaryUse?->vehicle_label_snapshot
                    ?? $primaryUse?->vehicle?->registration_number
                    ?? $primaryUse?->vehicle?->vehicle_number
                    ?? '-';
            }

            $customerModel = $customersWithContacts->get($inv->customer_id);
            $primaryContact = $customerModel?->contacts->first();
            $contactName = $primaryContact?->name ?? '';
            $contactPhone = $primaryContact?->phone ?? $inv->customer_phone ?? '';

            $customerId = (int) $inv->customer_id;
            if (! isset($groups[$customerId])) {
                $groups[$customerId] = [
                    'customer_id' => $customerId,
                    'lessee_code' => $inv->customer_code ?? 'CUST-'.$customerId,
                    'lessee_name' => $inv->customer_name,
                    'phone' => $contactPhone,
                    'contact_person' => $contactName,
                    'subtotal_original_amount' => '0.00',
                    'subtotal_balance_amount' => '0.00',
                    'invoices' => [],
                ];
            }

            $originalAmount = number_format((float) $inv->grand_total, 2, '.', '');
            $balanceAmount = number_format((float) $inv->remaining_amount, 2, '.', '');

            $groups[$customerId]['subtotal_original_amount'] = $this->math->add($groups[$customerId]['subtotal_original_amount'], $originalAmount, 2);
            $groups[$customerId]['subtotal_balance_amount'] = $this->math->add($groups[$customerId]['subtotal_balance_amount'], $balanceAmount, 2);

            $groups[$customerId]['invoices'][] = [
                'invoice_id' => $inv->invoice_id,
                'txn_type' => 'Invoice',
                'invoice_date' => $invoiceDate->format('d/m/Y'),
                'raw_invoice_date' => $inv->invoice_date,
                'invoice_number' => $inv->invoice_number,
                'agreement_duration' => $periodLabel,
                'agreement_reference' => $agreementRef ?? $matchedAgreement?->reference ?? '-',
                'vehicle_no' => $vehicleNo,
                'original_amount' => $originalAmount,
                'balance_amount' => $balanceAmount,
                'age_days' => $ageDays,
            ];

            $grandTotalOriginal = $this->math->add($grandTotalOriginal, $originalAmount, 2);
            $grandTotalBalance = $this->math->add($grandTotalBalance, $balanceAmount, 2);
            $totalInvoicesCount++;
        }

        $lesseeGroups = array_values($groups);
        $tenantName = TenantModel::query()->find($context->tenantId)?->name ?? (config('app.name') ?: 'AutoERP');

        return [
            'header' => [
                'company_name' => $tenantName,
                'system_title' => 'INTEGRATED RENT A CAR SYSTEM',
                'report_title' => "Lessee's Outstanding Age Analysis - 2 as at ".$asOfDate->format('d/m/Y'),
                'as_of_date' => $asOfDate->toDateString(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'total_lessees_count' => count($lesseeGroups),
                'total_invoices_count' => $totalInvoicesCount,
                'grand_total_original_amount' => $grandTotalOriginal,
                'grand_total_balance_amount' => $grandTotalBalance,
            ],
            'groups' => $lesseeGroups,
        ];
    }
}
