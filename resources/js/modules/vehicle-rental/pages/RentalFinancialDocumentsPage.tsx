import { useState } from 'react';
import { Link } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { notifySuccess } from '@/shared/notifications/appToast';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import { RentalReasonDialog } from '../components/RentalActionDialogs';
import { RentalCalculationDialog } from '../components/RentalCalculationDialog';
import {
    cancelRentalCalculation,
    createRentalFinancialDocument,
    listRentalCalculations,
} from '../vehicleRentalApi';
import { vehicleRentalPermissions } from '../vehicleRentalPermissions';
import type { RentalCalculation, RentalCalculationSide, RentalReference } from '../vehicleRentalTypes';

const PAGE_SIZE = 25;

interface RentalFinancialDocumentsPageProps {
    side: RentalCalculationSide;
}

function relationLabel(value?: RentalReference | null): string {
    return value?.name || value?.code || '—';
}

function dateLabel(value?: string | null): string {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString();
}

export function RentalFinancialDocumentsPage({ side }: RentalFinancialDocumentsPageProps) {
    const auth = useAuth();
    const canManage = hasPermission(auth, vehicleRentalPermissions.calculationsManage);
    const { confirm, confirmDialog } = useConfirmDialog();
    const [status, setStatus] = useState('calculated');
    const [page, setPage] = useState(1);
    const [createOpen, setCreateOpen] = useState(false);
    const [cancelling, setCancelling] = useState<RentalCalculation | null>(null);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi(
        (signal) => listRentalCalculations({
            calculation_side: side,
            calculation_status: status || undefined,
            page,
            per_page: PAGE_SIZE,
        }, signal),
        [side, status, page],
    );
    const customerSide = side === 'customer';
    const title = customerSide ? 'Customer invoices' : 'Owner settlements';
    const description = customerSide
        ? 'Prepare a customer billing period from finalized running charts, then create and post the customer invoice.'
        : 'Prepare an owner settlement period from finalized running charts, then create and post the Owner Payable Voucher.';
    const prepareLabel = customerSide ? 'Prepare billing period' : 'Prepare settlement period';
    const documentLabel = customerSide ? 'Customer invoice' : 'Owner Payable Voucher';
    const settlementLabel = customerSide ? 'Customer receipt' : 'Owner payment';

    const createDocument = async (calculation: RentalCalculation) => {
        const approved = await confirm({
            title: `Create ${documentLabel}?`,
            message: `Create and post ${documentLabel.toLowerCase()} from ${calculation.calculation_number}? Tax and Finance entries will be recorded atomically.`,
            confirmLabel: `Create ${documentLabel}`,
            danger: false,
        });
        if (!approved) return;

        setBusyId(calculation.id);
        setActionError(null);
        try {
            await createRentalFinancialDocument(calculation.id, {
                invoice_date: businessDateInputValue(),
                expected_version: calculation.row_version,
            });
            notifySuccess(`${documentLabel} created and posted successfully.`);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const columns: DataColumn<RentalCalculation>[] = [
        {
            key: 'calculation',
            header: customerSide ? 'Billing period' : 'Settlement period',
            render: (row) => <span className="font-semibold">{row.calculation_number}</span>,
        },
        { key: 'agreement', header: 'Agreement', render: (row) => relationLabel(row.agreement) },
        { key: 'period', header: 'Period', render: (row) => `${dateLabel(row.period_start)} → ${dateLabel(row.period_end)}` },
        { key: 'charts', header: 'Running charts', render: (row) => row.chart_count },
        {
            key: 'subtotal',
            header: 'Commercial subtotal',
            render: (row) => <MoneyDisplay value={row.subtotal_amount} currency={row.currency?.code ?? undefined} />,
        },
        {
            key: 'document',
            header: documentLabel,
            render: (row) => row.financial_document ? (
                <div className="space-y-1">
                    <Link className="font-medium text-sky-700 hover:underline" to={`/invoices/${row.financial_document.id}`}>
                        {row.financial_document.invoice_number}
                    </Link>
                    <StatusBadge status={row.financial_document.status} />
                </div>
            ) : <span className="text-slate-500">Not created</span>,
        },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    {canManage && row.status === 'calculated' && !row.financial_document && (
                        <Button className="min-h-9 px-3 py-1.5" loading={busyId === row.id} onClick={() => void createDocument(row)}>
                            Create {documentLabel}
                        </Button>
                    )}
                    {row.financial_document && (
                        <Button asChild variant="secondary" className="min-h-9 px-3 py-1.5">
                            <Link to={`/invoices/${row.financial_document.id}`}>View document</Link>
                        </Button>
                    )}
                    {row.financial_document && isPositiveDecimal(row.financial_document.balance_due) && (
                        <Button asChild className="min-h-9 px-3 py-1.5">
                            <Link to={`/payments/create?invoice_id=${row.financial_document.id}`}>{settlementLabel}</Link>
                        </Button>
                    )}
                    {canManage && row.status === 'calculated' && !row.financial_document && (
                        <Button variant="danger" className="min-h-9 px-3 py-1.5" onClick={() => setCancelling(row)}>Cancel period</Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <ContentHeader
                title={title}
                description={description}
                actions={canManage ? <Button onClick={() => setCreateOpen(true)}>{prepareLabel}</Button> : undefined}
            />
            <div className="mb-4 max-w-xs">
                <Select
                    label="Status"
                    value={status}
                    placeholder="All statuses"
                    options={[
                        { value: 'calculated', label: 'Active' },
                        { value: 'cancelled', label: 'Cancelled' },
                    ]}
                    onChange={(event) => { setStatus(event.target.value); setPage(1); }}
                />
            </div>
            <ErrorAlert error={actionError ?? result.error} inline />
            {result.loading
                ? <LoadingState />
                : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <RentalCalculationDialog
                open={createOpen}
                side={side}
                onClose={() => setCreateOpen(false)}
                onSaved={() => result.reload()}
            />
            <RentalReasonDialog
                open={Boolean(cancelling)}
                title={`Cancel ${cancelling?.calculation_number ?? 'period'}`}
                message="Cancelling releases finalized running charts for a corrected period on this financial side."
                confirmLabel="Cancel period"
                onClose={() => setCancelling(null)}
                onConfirm={async (reason) => {
                    if (!cancelling) return;
                    await cancelRentalCalculation(cancelling.id, cancelling.row_version, reason);
                    notifySuccess(customerSide ? 'Customer billing period cancelled.' : 'Owner settlement period cancelled.');
                    result.reload();
                }}
            />
            {confirmDialog}
        </>
    );
}
