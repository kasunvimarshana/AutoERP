<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Reporting\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\TimezoneResolverInterface;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Models\Payment;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;

final class CashierReportService
{
    private const ZERO = '0.000000';

    private const ZERO_DISPLAY = '0.00';

    private const CASH_IDENTIFIERS = ['cash'];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly ReportBrandingResolver $branding,
        private readonly ?TimezoneResolverInterface $timezoneResolver = null,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(int $tenantId, ?int $organizationUnitId, array $params): array
    {
        $today = $this->timezoneResolver !== null
            ? $this->timezoneResolver->todayDateString($tenantId, $organizationUnitId)
            : date('Y-m-d');
        $dateFrom = (string) ($params['date_from'] ?? substr($today, 0, 7).'-01');
        $dateTo = (string) ($params['date_to'] ?? $today);
        $cashierUserId = isset($params['cashier_user_id']) && is_numeric($params['cashier_user_id'])
            ? (int) $params['cashier_user_id']
            : null;
        $paymentMethod = isset($params['payment_method']) && trim((string) $params['payment_method']) !== ''
            ? trim((string) $params['payment_method'])
            : null;
        $direction = (string) ($params['direction'] ?? 'all');
        $documentStatus = (string) ($params['document_status'] ?? PaymentDocumentStatus::Approved->value);
        $search = isset($params['search']) && trim((string) $params['search']) !== ''
            ? trim((string) $params['search'])
            : null;
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 25)));

        $brand = $this->branding->resolve($tenantId, $organizationUnitId);
        $defaultCurrency = (string) ($brand['currency_code'] ?? 'LKR');
        $organizationUnitName = (string) ($brand['organization_unit_name'] ?: $brand['tenant_name']);

        $summary = $this->calculateSummary(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $methodBreakdowns = $this->calculatePaymentMethodBreakdown(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $cashierBreakdowns = $this->calculateCashierBreakdown(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $transactions = $this->queryTransactions(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
            $page,
            $perPage,
            $defaultCurrency,
        );

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'organization_unit' => $organizationUnitName,
            'currency' => $defaultCurrency,
            'summary' => $summary,
            'payment_methods' => $methodBreakdowns,
            'cashiers' => $cashierBreakdowns,
            'transactions' => $transactions,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, email: string}>
     */
    public function cashiersLookup(int $tenantId, ?int $organizationUnitId, ?string $search = null): array
    {
        $query = DB::table('users')
            ->where('users.tenant_id', $tenantId)
            ->whereNull('users.deleted_at')
            ->where(function (Builder $builder) use ($organizationUnitId): void {
                if ($organizationUnitId !== null) {
                    $builder->whereExists(function (Builder $sub) use ($organizationUnitId): void {
                        $sub->selectRaw('1')
                            ->from('user_organization_units')
                            ->whereColumn('user_organization_units.user_id', 'users.id')
                            ->where('user_organization_units.organization_unit_id', $organizationUnitId);
                    })->orWhereExists(function (Builder $sub) use ($organizationUnitId): void {
                        $sub->selectRaw('1')
                            ->from('payments')
                            ->whereColumn('payments.created_by', 'users.id')
                            ->where('payments.organization_unit_id', $organizationUnitId);
                    });
                }
            });

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('users.first_name', 'like', $term)
                    ->orWhere('users.last_name', 'like', $term)
                    ->orWhere('users.email', 'like', $term);
            });
        }

        $users = $query->select([
            'users.id',
            'users.first_name',
            'users.last_name',
            'users.email',
        ])
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->limit(50)
            ->get();

        return $users->map(fn (object $u): array => [
            'id' => (int) $u->id,
            'name' => trim($u->first_name.' '.($u->last_name ?? '')),
            'email' => (string) $u->email,
        ])->all();
    }

    public function definition(): ReportDefinition
    {
        $columns = [
            new ReportColumn('payment_date', 'Date', format: 'date'),
            new ReportColumn('created_at', 'Time', format: 'text'),
            new ReportColumn('payment_number', 'Receipt / Payment No.', format: 'text'),
            new ReportColumn('direction', 'Type', format: 'text'),
            new ReportColumn('cashier_name', 'Cashier', format: 'text'),
            new ReportColumn('party_name', 'Customer / Payee', format: 'text'),
            new ReportColumn('payment_methods', 'Payment Methods', format: 'text'),
            new ReportColumn('invoices', 'Settled Invoices', format: 'text'),
            new ReportColumn('total_amount', 'Amount', format: 'money'),
            new ReportColumn('document_status', 'Status', format: 'text'),
        ];

        return new ReportDefinition(
            key: 'cashier-report',
            title: 'Cashier Report',
            group: 'Invoice & Payment',
            model: Payment::class,
            columns: $columns,
            description: 'Comprehensive cashier collections, drawer reconciliation, tender breakdowns, and audit transactions.',
            orientation: 'landscape',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(int $tenantId, ?int $organizationUnitId, array $params): array
    {
        $dateFrom = (string) ($params['date_from'] ?? date('Y-m-01'));
        $dateTo = (string) ($params['date_to'] ?? date('Y-m-d'));
        $cashierUserId = isset($params['cashier_user_id']) && is_numeric($params['cashier_user_id'])
            ? (int) $params['cashier_user_id']
            : null;
        $paymentMethod = isset($params['payment_method']) && trim((string) $params['payment_method']) !== ''
            ? trim((string) $params['payment_method'])
            : null;
        $direction = (string) ($params['direction'] ?? 'all');
        $documentStatus = (string) ($params['document_status'] ?? PaymentDocumentStatus::Approved->value);
        $search = isset($params['search']) && trim((string) $params['search']) !== ''
            ? trim((string) $params['search'])
            : null;

        $brand = $this->branding->resolve($tenantId, $organizationUnitId);
        $defaultCurrency = (string) ($brand['currency_code'] ?? 'LKR');

        $query = $this->buildBasePaymentQuery(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $payments = $query
            ->leftJoin('users', 'users.id', '=', 'payments.created_by')
            ->select([
                'payments.id',
                'payments.payment_number',
                'payments.payment_date',
                'payments.direction',
                'payments.payment_type',
                'payments.document_status',
                'payments.currency_code_snapshot as currency_code',
                'payments.total_amount',
                'payments.party_name_snapshot as party_name',
                'payments.payee_name',
                'payments.reference_number',
                'payments.created_at',
                'users.first_name',
                'users.last_name',
            ])
            ->orderBy('payments.payment_date', 'asc')
            ->orderBy('payments.id', 'asc')
            ->limit(5000)
            ->get();

        $paymentIds = $payments->pluck('id')->all();
        $lines = $this->loadPaymentLines($tenantId, $paymentIds);
        $allocations = $this->loadPaymentAllocations($tenantId, $paymentIds);

        $rows = [];
        $totalInflow = self::ZERO;
        $totalOutflow = self::ZERO;

        foreach ($payments as $payment) {
            $paymentLines = $lines[$payment->id] ?? [];
            $paymentAllocs = $allocations[$payment->id] ?? [];

            $methodsSummary = [];
            foreach ($paymentLines as $l) {
                $methodsSummary[] = $l->payment_method_name_snapshot.': '.number_format((float) $l->amount, 2);
            }

            $invoicesSummary = [];
            foreach ($paymentAllocs as $a) {
                $invoicesSummary[] = $a->invoice_number.' ('.number_format((float) $a->allocated_amount, 2).')';
            }

            $cashierName = trim(($payment->first_name ?? '').' '.($payment->last_name ?? ''));
            if ($cashierName === '') {
                $cashierName = 'System / Unassigned';
            }

            $party = (string) ($payment->party_name ?: $payment->payee_name ?: 'Direct / Walk-in');
            $isInbound = $payment->direction === PaymentDirection::Inbound->value;
            $amount = (string) $payment->total_amount;

            if ($isInbound) {
                $totalInflow = $this->math->add($totalInflow, $amount);
            } else {
                $totalOutflow = $this->math->add($totalOutflow, $amount);
            }

            $rows[] = [
                'payment_date' => (string) $payment->payment_date,
                'created_at' => $payment->created_at
                    ? ($this->timezoneResolver !== null
                        ? $this->timezoneResolver->toCivilDateTime($payment->created_at, $tenantId, $organizationUnitId)->format('H:i:s')
                        : Carbon::parse($payment->created_at)->format('H:i:s'))
                    : '-',
                'payment_number' => (string) $payment->payment_number,
                'direction' => $isInbound ? 'Inflow (Collection)' : 'Outflow (Disbursement)',
                'cashier_name' => $cashierName,
                'party_name' => $party,
                'payment_methods' => implode(', ', $methodsSummary),
                'invoices' => implode(', ', $invoicesSummary),
                'total_amount' => number_format((float) $amount, 2).' '.($payment->currency_code ?: $defaultCurrency),
                'document_status' => ucfirst((string) $payment->document_status),
            ];
        }

        $netCollections = $this->math->sub($totalInflow, $totalOutflow);

        // Append total summary rows
        $rows[] = [
            'payment_date' => 'TOTAL INFLOW',
            'created_at' => '',
            'payment_number' => '',
            'direction' => '',
            'cashier_name' => '',
            'party_name' => '',
            'payment_methods' => '',
            'invoices' => '',
            'total_amount' => number_format((float) $totalInflow, 2).' '.$defaultCurrency,
            'document_status' => '',
        ];
        $rows[] = [
            'payment_date' => 'TOTAL OUTFLOW',
            'created_at' => '',
            'payment_number' => '',
            'direction' => '',
            'cashier_name' => '',
            'party_name' => '',
            'payment_methods' => '',
            'invoices' => '',
            'total_amount' => number_format((float) $totalOutflow, 2).' '.$defaultCurrency,
            'document_status' => '',
        ];
        $rows[] = [
            'payment_date' => 'NET COLLECTIONS',
            'created_at' => '',
            'payment_number' => '',
            'direction' => '',
            'cashier_name' => '',
            'party_name' => '',
            'payment_methods' => '',
            'invoices' => '',
            'total_amount' => number_format((float) $netCollections, 2).' '.$defaultCurrency,
            'document_status' => '',
        ];

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateSummary(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
        ?int $cashierUserId,
        ?string $paymentMethod,
        string $direction,
        string $documentStatus,
        ?string $search,
    ): array {
        $baseQuery = $this->buildBasePaymentQuery(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $totals = (clone $baseQuery)
            ->select([
                'payments.direction',
                DB::raw('COUNT(payments.id) as payment_count'),
                DB::raw('COALESCE(SUM(payments.total_amount), 0) as total_amount'),
            ])
            ->groupBy('payments.direction')
            ->get();

        $totalInflow = self::ZERO;
        $totalOutflow = self::ZERO;
        $inboundCount = 0;
        $outboundCount = 0;

        foreach ($totals as $row) {
            if ($row->direction === PaymentDirection::Inbound->value) {
                $totalInflow = (string) $row->total_amount;
                $inboundCount = (int) $row->payment_count;
            } elseif ($row->direction === PaymentDirection::Outbound->value) {
                $totalOutflow = (string) $row->total_amount;
                $outboundCount = (int) $row->payment_count;
            }
        }

        $netCollections = $this->math->sub($totalInflow, $totalOutflow);

        // Cash Tender Lines Query for exact physical Cash In Drawer calculation
        $linesQuery = DB::table('payment_lines')
            ->join('payments', 'payments.id', '=', 'payment_lines.payment_id')
            ->where('payment_lines.tenant_id', $tenantId)
            ->whereNull('payments.deleted_at')
            ->whereBetween('payments.payment_date', [$dateFrom, $dateTo]);

        $this->applyPaymentFilters(
            $linesQuery,
            $organizationUnitId,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $cashTotals = $linesQuery
            ->where(function (Builder $builder): void {
                $builder->whereIn(DB::raw('LOWER(payment_lines.payment_method_type_snapshot)'), self::CASH_IDENTIFIERS)
                    ->orWhereIn(DB::raw('LOWER(payment_lines.payment_method_code_snapshot)'), self::CASH_IDENTIFIERS);
            })
            ->select([
                'payments.direction',
                DB::raw('COALESCE(SUM(payment_lines.amount), 0) as cash_sum'),
            ])
            ->groupBy('payments.direction')
            ->get();

        $cashInflow = self::ZERO;
        $cashOutflow = self::ZERO;

        foreach ($cashTotals as $crow) {
            if ($crow->direction === PaymentDirection::Inbound->value) {
                $cashInflow = (string) $crow->cash_sum;
            } elseif ($crow->direction === PaymentDirection::Outbound->value) {
                $cashOutflow = (string) $crow->cash_sum;
            }
        }

        $netCashInDrawer = $this->math->sub($cashInflow, $cashOutflow);
        $nonCashTotal = $this->math->sub($netCollections, $netCashInDrawer);

        return [
            'total_inflow' => $this->formatMoney($totalInflow),
            'total_outflow' => $this->formatMoney($totalOutflow),
            'net_collections' => $this->formatMoney($netCollections),
            'cash_inflow' => $this->formatMoney($cashInflow),
            'cash_outflow' => $this->formatMoney($cashOutflow),
            'net_cash_in_drawer' => $this->formatMoney($netCashInDrawer),
            'non_cash_total' => $this->formatMoney($nonCashTotal),
            'total_transactions' => $inboundCount + $outboundCount,
            'inbound_count' => $inboundCount,
            'outbound_count' => $outboundCount,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function calculatePaymentMethodBreakdown(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
        ?int $cashierUserId,
        ?string $paymentMethod,
        string $direction,
        string $documentStatus,
        ?string $search,
    ): array {
        $linesQuery = DB::table('payment_lines')
            ->join('payments', 'payments.id', '=', 'payment_lines.payment_id')
            ->where('payment_lines.tenant_id', $tenantId)
            ->whereNull('payments.deleted_at')
            ->whereBetween('payments.payment_date', [$dateFrom, $dateTo]);

        $this->applyPaymentFilters(
            $linesQuery,
            $organizationUnitId,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $methodRows = $linesQuery
            ->select([
                'payment_lines.payment_method_code_snapshot as method_code',
                'payment_lines.payment_method_name_snapshot as method_name',
                'payment_lines.payment_method_type_snapshot as method_type',
                'payments.direction',
                DB::raw('COUNT(DISTINCT payments.id) as payment_count'),
                DB::raw('COALESCE(SUM(payment_lines.amount), 0) as line_amount'),
            ])
            ->groupBy([
                'payment_lines.payment_method_code_snapshot',
                'payment_lines.payment_method_name_snapshot',
                'payment_lines.payment_method_type_snapshot',
                'payments.direction',
            ])
            ->orderBy('payment_lines.payment_method_name_snapshot')
            ->get();

        $grouped = [];
        foreach ($methodRows as $row) {
            $code = (string) $row->method_code;
            if (! isset($grouped[$code])) {
                $grouped[$code] = [
                    'key' => $code,
                    'name' => (string) $row->method_name,
                    'type' => (string) $row->method_type,
                    'inflow' => self::ZERO,
                    'inflow_count' => 0,
                    'outflow' => self::ZERO,
                    'outflow_count' => 0,
                    'net' => self::ZERO,
                    'total_count' => 0,
                ];
            }

            if ($row->direction === PaymentDirection::Inbound->value) {
                $grouped[$code]['inflow'] = (string) $row->line_amount;
                $grouped[$code]['inflow_count'] = (int) $row->payment_count;
            } elseif ($row->direction === PaymentDirection::Outbound->value) {
                $grouped[$code]['outflow'] = (string) $row->line_amount;
                $grouped[$code]['outflow_count'] = (int) $row->payment_count;
            }
        }

        $result = [];
        foreach ($grouped as $item) {
            $net = $this->math->sub($item['inflow'], $item['outflow']);
            $result[] = [
                'key' => $item['key'],
                'name' => $item['name'],
                'type' => $item['type'],
                'inflow' => $this->formatMoney($item['inflow']),
                'inflow_count' => $item['inflow_count'],
                'outflow' => $this->formatMoney($item['outflow']),
                'outflow_count' => $item['outflow_count'],
                'net' => $this->formatMoney($net),
                'total_count' => $item['inflow_count'] + $item['outflow_count'],
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function calculateCashierBreakdown(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
        ?int $cashierUserId,
        ?string $paymentMethod,
        string $direction,
        string $documentStatus,
        ?string $search,
    ): array {
        $baseQuery = $this->buildBasePaymentQuery(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $cashierRows = (clone $baseQuery)
            ->leftJoin('users', 'users.id', '=', 'payments.created_by')
            ->select([
                'payments.created_by as cashier_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'payments.direction',
                DB::raw('COUNT(payments.id) as payment_count'),
                DB::raw('COALESCE(SUM(payments.total_amount), 0) as total_amount'),
            ])
            ->groupBy([
                'payments.created_by',
                'users.first_name',
                'users.last_name',
                'users.email',
                'payments.direction',
            ])
            ->get();

        // Also query cash tender line sums per cashier
        $linesQuery = DB::table('payment_lines')
            ->join('payments', 'payments.id', '=', 'payment_lines.payment_id')
            ->where('payment_lines.tenant_id', $tenantId)
            ->whereNull('payments.deleted_at')
            ->whereBetween('payments.payment_date', [$dateFrom, $dateTo])
            ->where(function (Builder $builder): void {
                $builder->whereIn(DB::raw('LOWER(payment_lines.payment_method_type_snapshot)'), self::CASH_IDENTIFIERS)
                    ->orWhereIn(DB::raw('LOWER(payment_lines.payment_method_code_snapshot)'), self::CASH_IDENTIFIERS);
            });

        $this->applyPaymentFilters(
            $linesQuery,
            $organizationUnitId,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $cashByCashier = $linesQuery
            ->select([
                'payments.created_by as cashier_id',
                'payments.direction',
                DB::raw('COALESCE(SUM(payment_lines.amount), 0) as cash_sum'),
            ])
            ->groupBy([
                'payments.created_by',
                'payments.direction',
            ])
            ->get();

        $cashMap = [];
        foreach ($cashByCashier as $c) {
            $cid = (int) ($c->cashier_id ?? 0);
            $cashMap[$cid] ??= ['inflow' => self::ZERO, 'outflow' => self::ZERO];
            if ($c->direction === PaymentDirection::Inbound->value) {
                $cashMap[$cid]['inflow'] = (string) $c->cash_sum;
            } else {
                $cashMap[$cid]['outflow'] = (string) $c->cash_sum;
            }
        }

        $grouped = [];
        foreach ($cashierRows as $row) {
            $cid = (int) ($row->cashier_id ?? 0);
            if (! isset($grouped[$cid])) {
                $name = trim(($row->first_name ?? '').' '.($row->last_name ?? ''));
                if ($name === '') {
                    $name = 'System / Unassigned';
                }
                $grouped[$cid] = [
                    'cashier_id' => $row->cashier_id !== null ? (int) $row->cashier_id : null,
                    'cashier_name' => $name,
                    'cashier_email' => (string) ($row->email ?? ''),
                    'inflow' => self::ZERO,
                    'outflow' => self::ZERO,
                    'transaction_count' => 0,
                ];
            }

            if ($row->direction === PaymentDirection::Inbound->value) {
                $grouped[$cid]['inflow'] = (string) $row->total_amount;
                $grouped[$cid]['transaction_count'] += (int) $row->payment_count;
            } elseif ($row->direction === PaymentDirection::Outbound->value) {
                $grouped[$cid]['outflow'] = (string) $row->total_amount;
                $grouped[$cid]['transaction_count'] += (int) $row->payment_count;
            }
        }

        $result = [];
        foreach ($grouped as $cid => $item) {
            $net = $this->math->sub($item['inflow'], $item['outflow']);
            $cashIn = $cashMap[$cid]['inflow'] ?? self::ZERO;
            $cashOut = $cashMap[$cid]['outflow'] ?? self::ZERO;
            $cashNet = $this->math->sub($cashIn, $cashOut);
            $nonCashNet = $this->math->sub($net, $cashNet);

            $result[] = [
                'cashier_id' => $item['cashier_id'],
                'cashier_name' => $item['cashier_name'],
                'cashier_email' => $item['cashier_email'],
                'inflow' => $this->formatMoney($item['inflow']),
                'outflow' => $this->formatMoney($item['outflow']),
                'net' => $this->formatMoney($net),
                'cash_net' => $this->formatMoney($cashNet),
                'non_cash_net' => $this->formatMoney($nonCashNet),
                'transaction_count' => $item['transaction_count'],
            ];
        }

        usort($result, fn (array $a, array $b): int => strcmp($a['cashier_name'], $b['cashier_name']));

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function queryTransactions(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
        ?int $cashierUserId,
        ?string $paymentMethod,
        string $direction,
        string $documentStatus,
        ?string $search,
        int $page,
        int $perPage,
        string $defaultCurrency,
    ): array {
        $query = $this->buildBasePaymentQuery(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        $paginator = $query
            ->leftJoin('users', 'users.id', '=', 'payments.created_by')
            ->leftJoin('organization_units', 'organization_units.id', '=', 'payments.organization_unit_id')
            ->select([
                'payments.id',
                'payments.payment_number',
                'payments.payment_date',
                'payments.direction',
                'payments.payment_type',
                'payments.document_status',
                'payments.posting_status',
                'payments.currency_code_snapshot as currency_code',
                'payments.total_amount',
                'payments.allocated_amount',
                'payments.unapplied_amount',
                'payments.party_name_snapshot as party_name',
                'payments.payee_name',
                'payments.reference_number',
                'payments.created_by as cashier_id',
                'payments.created_at',
                'users.first_name',
                'users.last_name',
                'organization_units.name as organization_unit_name',
            ])
            ->orderBy('payments.payment_date', 'desc')
            ->orderBy('payments.id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $paymentIds = collect($paginator->items())->pluck('id')->all();
        $lines = $this->loadPaymentLines($tenantId, $paymentIds);
        $allocations = $this->loadPaymentAllocations($tenantId, $paymentIds);

        $data = collect($paginator->items())->map(function (object $p) use ($lines, $allocations, $defaultCurrency): array {
            $paymentLines = $lines[$p->id] ?? [];
            $paymentAllocs = $allocations[$p->id] ?? [];

            $cashierName = trim(($p->first_name ?? '').' '.($p->last_name ?? ''));
            if ($cashierName === '') {
                $cashierName = 'System / Unassigned';
            }

            $isInbound = $p->direction === PaymentDirection::Inbound->value;

            return [
                'id' => (int) $p->id,
                'payment_number' => (string) $p->payment_number,
                'payment_date' => (string) $p->payment_date,
                'created_at' => $p->created_at
                    ? ($this->timezoneResolver !== null
                        ? $this->timezoneResolver->toCivilDateTime($p->created_at, (int) ($p->tenant_id ?? 0), isset($p->organization_unit_id) && is_numeric($p->organization_unit_id) ? (int) $p->organization_unit_id : null)->toIso8601String()
                        : Carbon::parse($p->created_at)->toIso8601String())
                    : null,
                'direction' => (string) $p->direction,
                'direction_label' => $isInbound ? 'Collection' : 'Disbursement / Refund',
                'payment_type' => (string) $p->payment_type,
                'document_status' => (string) $p->document_status,
                'posting_status' => (string) $p->posting_status,
                'currency_code' => (string) ($p->currency_code ?: $defaultCurrency),
                'total_amount' => $this->formatMoney((string) $p->total_amount),
                'party_name' => (string) ($p->party_name ?: $p->payee_name ?: 'Direct / Walk-in'),
                'reference_number' => (string) ($p->reference_number ?? ''),
                'cashier_id' => $p->cashier_id !== null ? (int) $p->cashier_id : null,
                'cashier_name' => $cashierName,
                'organization_unit_name' => (string) ($p->organization_unit_name ?? ''),
                'lines' => array_map(fn (object $l): array => [
                    'method_code' => (string) $l->payment_method_code_snapshot,
                    'method_name' => (string) $l->payment_method_name_snapshot,
                    'method_type' => (string) $l->payment_method_type_snapshot,
                    'amount' => $this->formatMoney((string) $l->amount),
                    'reference_number' => (string) ($l->reference_number ?? ''),
                    'instrument_number' => (string) ($l->instrument_number ?? ''),
                ], $paymentLines),
                'allocations' => array_map(fn (object $a): array => [
                    'invoice_number' => (string) $a->invoice_number,
                    'allocated_amount' => $this->formatMoney((string) $a->allocated_amount),
                ], $paymentAllocs),
            ];
        })->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }

    /**
     * @param  array<int, int|string>  $paymentIds
     * @return array<int, array<int, object>>
     */
    private function loadPaymentLines(int $tenantId, array $paymentIds): array
    {
        if ($paymentIds === []) {
            return [];
        }

        $lines = DB::table('payment_lines')
            ->where('tenant_id', $tenantId)
            ->whereIn('payment_id', $paymentIds)
            ->orderBy('line_number')
            ->select([
                'payment_id',
                'line_number',
                'payment_method_code_snapshot',
                'payment_method_name_snapshot',
                'payment_method_type_snapshot',
                'amount',
                'reference_number',
                'instrument_number',
            ])
            ->get();

        $grouped = [];
        foreach ($lines as $line) {
            $grouped[(int) $line->payment_id][] = $line;
        }

        return $grouped;
    }

    /**
     * @param  array<int, int|string>  $paymentIds
     * @return array<int, array<int, object>>
     */
    private function loadPaymentAllocations(int $tenantId, array $paymentIds): array
    {
        if ($paymentIds === []) {
            return [];
        }

        $allocations = DB::table('payment_allocations')
            ->join('invoices', 'invoices.id', '=', 'payment_allocations.invoice_id')
            ->where('payment_allocations.tenant_id', $tenantId)
            ->where('payment_allocations.status', 'active')
            ->whereIn('payment_allocations.payment_id', $paymentIds)
            ->select([
                'payment_allocations.payment_id',
                'payment_allocations.allocated_amount',
                'invoices.invoice_number',
            ])
            ->get();

        $grouped = [];
        foreach ($allocations as $alloc) {
            $grouped[(int) $alloc->payment_id][] = $alloc;
        }

        return $grouped;
    }

    private function buildBasePaymentQuery(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
        ?int $cashierUserId,
        ?string $paymentMethod,
        string $direction,
        string $documentStatus,
        ?string $search,
    ): Builder {
        $query = DB::table('payments')
            ->where('payments.tenant_id', $tenantId)
            ->whereNull('payments.deleted_at')
            ->whereBetween('payments.payment_date', [$dateFrom, $dateTo]);

        $this->applyPaymentFilters(
            $query,
            $organizationUnitId,
            $cashierUserId,
            $paymentMethod,
            $direction,
            $documentStatus,
            $search,
        );

        return $query;
    }

    private function applyPaymentFilters(
        Builder $query,
        ?int $organizationUnitId,
        ?int $cashierUserId,
        ?string $paymentMethod,
        string $direction,
        string $documentStatus,
        ?string $search,
    ): void {
        if ($organizationUnitId !== null) {
            $query->where('payments.organization_unit_id', $organizationUnitId);
        }

        if ($cashierUserId !== null) {
            $query->where('payments.created_by', $cashierUserId);
        }

        if ($direction !== 'all') {
            $query->where('payments.direction', $direction);
        }

        if ($documentStatus !== 'all') {
            $query->where('payments.document_status', $documentStatus);
        }

        if ($paymentMethod !== null && $paymentMethod !== '' && $paymentMethod !== 'all') {
            $methodLower = strtolower($paymentMethod);
            $query->whereExists(function (Builder $exists) use ($methodLower): void {
                $exists->selectRaw('1')
                    ->from('payment_lines')
                    ->whereColumn('payment_lines.payment_id', 'payments.id')
                    ->where(function (Builder $scope) use ($methodLower): void {
                        $scope->where(DB::raw('LOWER(payment_lines.payment_method_type_snapshot)'), $methodLower)
                            ->orWhere(DB::raw('LOWER(payment_lines.payment_method_code_snapshot)'), $methodLower);
                    });
            });
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $scope) use ($term): void {
                $scope->where('payments.payment_number', 'like', $term)
                    ->orWhere('payments.reference_number', 'like', $term)
                    ->orWhere('payments.party_name_snapshot', 'like', $term)
                    ->orWhere('payments.payee_name', 'like', $term)
                    ->orWhereExists(function (Builder $alloc) use ($term): void {
                        $alloc->selectRaw('1')
                            ->from('payment_allocations')
                            ->join('invoices', 'invoices.id', '=', 'payment_allocations.invoice_id')
                            ->whereColumn('payment_allocations.payment_id', 'payments.id')
                            ->where('invoices.invoice_number', 'like', $term);
                    });
            });
        }
    }

    private function formatMoney(string $amount): string
    {
        return $this->math->normalize($amount, 2);
    }
}
