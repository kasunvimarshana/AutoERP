import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { readableRelation } from '@/shared/utils/object';
import { RentalAgreementLookupSelect } from '../components/RentalLookups';
import { RentalPage } from '../components/RentalPage';
import { agreementDetailPath } from '../rentalAgreementPresentation';
import {
    calculateRentalAgreement,
    createRentalInvoice,
    listRentalCalculationRuns,
    transitionRentalCalculationRun,
} from '../vehicleRentalApi';
import { vehicleRentalPermissions } from '../vehicleRentalPermissions';
import type { RentalCalculationLine, RentalCalculationRun } from '../vehicleRentalTypes';

type VersionedNamedResource = NamedResource & { row_version?: number };

function defaultBillingPeriod() {
    const today = businessDateInputValue();
    return { start: `${today.slice(0, 7)}-01`, end: today };
}

function calculationLineSourceLabel(line: RentalCalculationLine): string {
    const usage = line.source.usage_context?.usage;
    if (usage) {
        return usage.usage_number ?? usage.name ?? 'Approved running chart';
    }

    const expense = line.source.expense_allocation?.expense;
    if (expense) {
        return expense.expense_number ?? expense.name ?? 'Approved rental expense';
    }

    const custodyEvent = line.source.custody_event_item?.custody_event;
    if (custodyEvent) {
        return custodyEvent.event_number ?? custodyEvent.name ?? 'Confirmed custody event';
    }

    return line.source_type.replaceAll('_', ' ');
}

function runMoney(run: RentalCalculationRun, value: string): string {
    const currencyCode = run.currency?.code;
    return currencyCode ? formatMoney(value, currencyCode) : value;
}

export default function RentalBillingPage() {
    const auth = useAuth();
    const navigate = useNavigate();
    const initialPeriod = defaultBillingPeriod();
    const [agreement, setAgreement] = useState<VersionedNamedResource | null>(null);
    const [financialSide, setFinancialSide] = useState<'revenue' | 'cost'>('revenue');
    const [periodStart, setPeriodStart] = useState(initialPeriod.start);
    const [periodEnd, setPeriodEnd] = useState(initialPeriod.end);
    const [reviewingRun, setReviewingRun] = useState<RentalCalculationRun | null>(null);
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const runs = useApi(
        (signal) => listRentalCalculationRuns(
            {
                agreement_id: agreement?.id,
                financial_side: financialSide,
                per_page: 50,
            },
            signal,
        ),
        [agreement?.id, financialSide],
    );

    const canCalculate = hasPermission(auth, vehicleRentalPermissions.calculationsManage);
    const canApprove = hasPermission(auth, vehicleRentalPermissions.calculationsApprove);
    const canCreateDocument = hasPermission(auth, vehicleRentalPermissions.financialCreate);

    const calculate = async (event: FormEvent) => {
        event.preventDefault();
        if (!agreement?.row_version) return;

        setSaving(true);
        setActionError(null);
        try {
            const run = await calculateRentalAgreement(agreement.id, agreement.row_version, {
                financial_side: financialSide,
                period_start: periodStart,
                period_end: periodEnd,
            });
            const nextAgreementVersion = run.billing_period?.agreement?.row_version;
            if (nextAgreementVersion) {
                setAgreement((current) => current === null
                    ? current
                    : { ...current, row_version: nextAgreementVersion });
            }
            setReviewingRun(run);
            runs.reload();
        } catch (error: unknown) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const transition = async (run: RentalCalculationRun, status: string) => {
        setActionError(null);
        try {
            await transitionRentalCalculationRun(run.id, run.row_version, status);
            setReviewingRun(null);
            runs.reload();
        } catch (error: unknown) {
            setActionError(toApiError(error));
        }
    };

    const createDocument = async (run: RentalCalculationRun) => {
        setActionError(null);
        try {
            const documentDate = businessDateInputValue();
            const invoice = await createRentalInvoice(run.id, run.row_version, {
                invoice_date: documentDate,
                status: 'draft',
                notes: `${run.billing_period?.financial_side === 'cost' ? 'Owner rental payable' : 'Lessee rental invoice'} from approved rental calculation`,
            });
            runs.reload();
            navigate(`/invoices/${invoice.id}?from=vehicle-rental`);
        } catch (error: unknown) {
            setActionError(toApiError(error));
        }
    };

    const columns: DataColumn<RentalCalculationRun>[] = [
        { key: 'run', header: 'Run', render: (row) => `Billing run v${row.run_version}` },
        {
            key: 'agreement',
            header: 'Agreement',
            render: (row) => row.billing_period?.agreement ? (
                <Link
                    className="font-semibold text-blue-700"
                    to={agreementDetailPath(
                        row.billing_period.financial_side === 'cost' ? 'lessor' : 'lessee',
                        row.billing_period.agreement.id,
                    )}
                >
                    {readableRelation(row.billing_period.agreement)}
                </Link>
            ) : '-',
        },
        {
            key: 'side',
            header: 'Side',
            render: (row) => row.billing_period?.financial_side === 'cost' ? 'Owner cost' : 'Lessee revenue',
        },
        {
            key: 'period',
            header: 'Period',
            render: (row) => `${formatDate(row.billing_period?.period_start)} - ${formatDate(row.billing_period?.period_end)}`,
        },
        { key: 'total', header: 'Grand total', render: (row) => runMoney(row, row.grand_total) },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (
                <div className="space-y-1">
                    <StatusBadge status={row.calculation_status} />
                    <div className="text-xs text-slate-500">{row.document_status.replaceAll('_', ' ')}</div>
                </div>
            ),
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <Button variant="ghost" onClick={() => setReviewingRun(row)}>
                    Review
                </Button>
            ),
        },
    ];

    const reviewColumns: DataColumn<RentalCalculationLine>[] = [
        { key: 'line', header: '#', render: (line) => line.line_number },
        {
            key: 'component',
            header: 'Component',
            render: (line) => (
                <div>
                    <div className="font-medium text-slate-900">{line.description}</div>
                    <div className="text-xs text-slate-500">{line.component_code.replaceAll('_', ' ')}</div>
                </div>
            ),
        },
        { key: 'source', header: 'Source evidence', render: calculationLineSourceLabel },
        { key: 'measured', header: 'Measured', render: (line) => `${line.measured_quantity} ${line.unit ?? ''}`.trim() },
        { key: 'allowed', header: 'Allowed', render: (line) => `${line.allowed_quantity} ${line.unit ?? ''}`.trim() },
        { key: 'chargeable', header: 'Chargeable', render: (line) => `${line.chargeable_quantity} ${line.unit ?? ''}`.trim() },
        {
            key: 'rate',
            header: 'Rate',
            render: (line) => reviewingRun ? runMoney(reviewingRun, line.rate) : line.rate,
        },
        {
            key: 'tax',
            header: 'Tax',
            render: (line) => reviewingRun ? runMoney(reviewingRun, line.tax_amount) : line.tax_amount,
        },
        {
            key: 'withholding',
            header: 'Withholding',
            render: (line) => reviewingRun ? runMoney(reviewingRun, line.withholding_amount) : line.withholding_amount,
        },
        {
            key: 'total',
            header: 'Total',
            render: (line) => reviewingRun ? runMoney(reviewingRun, line.total_amount) : line.total_amount,
        },
    ];

    return (
        <RentalPage>
            <ContentHeader
                title="Rental billing"
                description="Calculate customer revenue and owner cost independently from the same approved usage stream."
            />
            <ErrorAlert error={actionError ?? runs.error} />
            {canCalculate && (
                <Panel title="New calculation">
                    <form onSubmit={calculate} className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <RentalAgreementLookupSelect
                            value={agreement}
                            onChange={(value) => {
                                setAgreement(value);
                                setReviewingRun(null);
                            }}
                            agreementKind={financialSide === 'revenue' ? 'customer_rental' : 'owner_supply'}
                            required
                        />
                        <Select
                            label="Financial side"
                            value={financialSide}
                            onChange={(event) => {
                                setFinancialSide(event.target.value as 'revenue' | 'cost');
                                setAgreement(null);
                                setReviewingRun(null);
                            }}
                            options={[
                                { value: 'revenue', label: 'Lessee revenue' },
                                { value: 'cost', label: 'Owner cost' },
                            ]}
                        />
                        <Input
                            label="Period start"
                            type="date"
                            required
                            max={periodEnd || undefined}
                            value={periodStart}
                            onChange={(event) => setPeriodStart(event.target.value)}
                        />
                        <Input
                            label="Period end"
                            type="date"
                            required
                            min={periodStart || undefined}
                            value={periodEnd}
                            onChange={(event) => setPeriodEnd(event.target.value)}
                        />
                        <div className="flex items-end">
                            <Button
                                type="submit"
                                loading={saving}
                                disabled={!agreement?.row_version || !periodStart || !periodEnd || periodStart > periodEnd}
                            >
                                Calculate
                            </Button>
                        </div>
                    </form>
                </Panel>
            )}
            <div className="mt-5">
                {runs.loading ? (
                    <LoadingState />
                ) : (
                    <DataTable
                        rows={runs.data?.data ?? []}
                        columns={columns}
                        rowKey={(row) => row.id}
                        emptyMessage="No rental calculation runs found."
                    />
                )}
            </div>
            {reviewingRun && (
                <div className="mt-5">
                    <Panel
                        title={reviewingRun.billing_period?.financial_side === 'cost'
                            ? 'Owner payable calculation review'
                            : 'Lessee invoice calculation review'}
                    >
                        <div className="mb-4 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-2 xl:grid-cols-5">
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Agreement</div>
                                <div className="mt-1 font-medium text-slate-900">
                                    {readableRelation(reviewingRun.billing_period?.agreement)}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Period</div>
                                <div className="mt-1 text-slate-900">
                                    {formatDate(reviewingRun.billing_period?.period_start)} - {formatDate(reviewingRun.billing_period?.period_end)}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Net</div>
                                <div className="mt-1 text-slate-900">{runMoney(reviewingRun, reviewingRun.net_total)}</div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Tax / withholding</div>
                                <div className="mt-1 text-slate-900">
                                    {runMoney(reviewingRun, reviewingRun.tax_total)} / {runMoney(reviewingRun, reviewingRun.withholding_total)}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Grand total</div>
                                <div className="mt-1 font-semibold text-slate-900">
                                    {runMoney(reviewingRun, reviewingRun.grand_total)}
                                </div>
                            </div>
                        </div>

                        <DataTable
                            rows={reviewingRun.lines ?? []}
                            columns={reviewColumns}
                            rowKey={(line) => line.id}
                            emptyMessage="This calculation has no lines to review."
                        />

                        <div className="mt-4 flex flex-wrap justify-end gap-2">
                            <Button variant="ghost" onClick={() => setReviewingRun(null)}>
                                Close review
                            </Button>
                            {canCalculate && reviewingRun.calculation_status === 'calculated' && (
                                <Button variant="secondary" onClick={() => void transition(reviewingRun, 'submitted')}>
                                    Submit calculation
                                </Button>
                            )}
                            {canApprove && reviewingRun.calculation_status === 'submitted' && (
                                <Button variant="secondary" onClick={() => void transition(reviewingRun, 'approved')}>
                                    Approve calculation
                                </Button>
                            )}
                            {canCreateDocument
                                && reviewingRun.calculation_status === 'approved'
                                && reviewingRun.document_status !== 'generated' && (
                                <Button onClick={() => void createDocument(reviewingRun)}>
                                    {reviewingRun.billing_period?.financial_side === 'cost'
                                        ? 'Create owner payable'
                                        : 'Create lessee invoice'}
                                </Button>
                            )}
                        </div>
                    </Panel>
                </div>
            )}
        </RentalPage>
    );
}
