import { useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
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
import { readableRelation } from '@/shared/utils/object';
import { RentalAgreementLookupSelect } from '../components/RentalLookups';
import { RentalPage } from '../components/RentalPage';
import {
    calculateRentalAgreement,
    createRentalInvoice,
    listRentalCalculationRuns,
    transitionRentalCalculationRun,
} from '../vehicleRentalApi';
import { vehicleRentalPermissions } from '../vehicleRentalPermissions';
import type { RentalCalculationRun } from '../vehicleRentalTypes';

function defaultBillingPeriod() {
    const today = businessDateInputValue();
    return { start: `${today.slice(0, 7)}-01`, end: today };
}

export default function RentalBillingPage() {
    const auth = useAuth();
    const initialPeriod = defaultBillingPeriod();
    const [agreement, setAgreement] = useState<NamedResource | null>(null);
    const [financialSide, setFinancialSide] = useState<'revenue' | 'cost'>('revenue');
    const [periodStart, setPeriodStart] = useState(initialPeriod.start);
    const [periodEnd, setPeriodEnd] = useState(initialPeriod.end);
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
        if (!agreement) return;

        setSaving(true);
        setActionError(null);
        try {
            await calculateRentalAgreement(agreement.id, {
                financial_side: financialSide,
                period_start: periodStart,
                period_end: periodEnd,
            });
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
            runs.reload();
        } catch (error: unknown) {
            setActionError(toApiError(error));
        }
    };

    const createDocument = async (run: RentalCalculationRun) => {
        setActionError(null);
        try {
            const documentDate = businessDateInputValue();
            await createRentalInvoice(run.id, {
                invoice_date: documentDate,
                due_date: documentDate,
                status: 'draft',
                notes: `${run.billing_period?.financial_side === 'cost' ? 'Owner rental payable' : 'Customer rental invoice'} from approved rental calculation`,
            });
            runs.reload();
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
                    to={`/vehicle-rental/agreements/${row.billing_period.agreement.id}`}
                >
                    {readableRelation(row.billing_period.agreement)}
                </Link>
            ) : '-',
        },
        { key: 'side', header: 'Side', render: (row) => row.billing_period?.financial_side ?? '-' },
        {
            key: 'period',
            header: 'Period',
            render: (row) => `${formatDate(row.billing_period?.period_start)} – ${formatDate(row.billing_period?.period_end)}`,
        },
        { key: 'total', header: 'Grand total', render: (row) => row.grand_total },
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
                <div className="flex justify-end gap-2">
                    {canCalculate && row.calculation_status === 'calculated' && (
                        <Button variant="ghost" onClick={() => void transition(row, 'submitted')}>
                            Submit
                        </Button>
                    )}
                    {canApprove && row.calculation_status === 'submitted' && (
                        <Button variant="ghost" onClick={() => void transition(row, 'approved')}>
                            Approve
                        </Button>
                    )}
                    {canCreateDocument
                        && row.calculation_status === 'approved'
                        && row.document_status !== 'generated' && (
                        <Button variant="secondary" onClick={() => void createDocument(row)}>
                            {row.billing_period?.financial_side === 'cost' ? 'Create payable' : 'Create invoice'}
                        </Button>
                    )}
                </div>
            ),
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
                            onChange={setAgreement}
                            direction={financialSide === 'revenue' ? 'inbound' : 'outbound'}
                            required
                        />
                        <Select
                            label="Financial side"
                            value={financialSide}
                            onChange={(event) => {
                                setFinancialSide(event.target.value as 'revenue' | 'cost');
                                setAgreement(null);
                            }}
                            options={[
                                { value: 'revenue', label: 'Customer revenue' },
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
                                disabled={!agreement || !periodStart || !periodEnd || periodStart > periodEnd}
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
        </RentalPage>
    );
}
