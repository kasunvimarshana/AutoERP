import { useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import {
    approveInvoice,
    getInvoice,
    getInvoiceAdjustments,
    getInvoiceBalance,
    getInvoiceSources,
    postInvoice,
} from '../invoiceApi';
import { hasInvoicePermission, invoicePermissions } from '../invoicePermissions';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Tabs, type TabItem } from '@/shared/components/Tabs';
import { Panel } from '@/shared/components/Panel';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { RecordTable } from '@/shared/components/RecordTable';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { formatDate } from '@/shared/utils/formatDate';
import { humanize, readableRelation } from '@/shared/utils/object';
import { Button, LinkButton } from '@/shared/components/Button';
import { useAuth } from '@/modules/auth/AuthProvider';
import RentalInvoicePrintView from '../components/RentalInvoicePrintView';

type Tab = 'summary' | 'balance' | 'sources' | 'lines' | 'adjustments' | 'rental_invoice';

const summaryTab: TabItem<Tab> = { id: 'summary', label: 'Summary' };
const linesTab: TabItem<Tab> = { id: 'lines', label: 'Lines' };

export default function InvoiceDetailPage() {
    const id = Number(useParams().id);
    const auth = useAuth();
    const canViewBalance = hasInvoicePermission(auth, invoicePermissions.balanceView);
    const canViewSources = hasInvoicePermission(auth, invoicePermissions.sourcesView);
    const [searchParams] = useSearchParams();
    const initialTab: Tab = searchParams.get('tab') === 'rental_invoice' ? 'rental_invoice' : 'summary';
    const tabState = useOnDemandTab<Tab>(initialTab);
    const invoice = useApi((signal) => getInvoice(id, signal), [id]);
    const isRental = invoice.data?.invoice_type === 'rental' || searchParams.get('type') === 'rental';
    const tabs: TabItem<Tab>[] = [
        summaryTab,
        ...(isRental ? [{ id: 'rental_invoice' as const, label: 'Rental Tax Invoice (Print)' }] : []),
        ...(canViewBalance ? [{ id: 'balance' as const, label: 'Balance' }] : []),
        ...(canViewSources ? [
            { id: 'sources' as const, label: 'Sources' },
            { id: 'adjustments' as const, label: 'Adjustments' },
        ] : []),
        linesTab,
        ...(!isRental ? [{ id: 'rental_invoice' as const, label: 'Rental Tax Invoice' }] : []),
    ];
    const balance = useApi(
        (signal) => getInvoiceBalance(id, signal),
        [id],
        canViewBalance && tabState.openedTabs.has('balance'),
    );
    const sources = useApi(
        (signal) => getInvoiceSources(id, signal),
        [id],
        canViewSources && tabState.openedTabs.has('sources'),
    );
    const adjustments = useApi(
        (signal) => getInvoiceAdjustments(id, signal),
        [id],
        canViewSources && tabState.openedTabs.has('adjustments'),
    );
    const canApprove = hasInvoicePermission(auth, invoicePermissions.approve);
    const canPost = hasInvoicePermission(auth, invoicePermissions.post);
    const [actionBusy, setActionBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    if (invoice.loading) return <LoadingState />;
    if (!invoice.data) return <ErrorAlert error={invoice.error} />;
    const value = invoice.data;
    const fromPurchase = searchParams.get('from') === 'purchase';
    const isSupplierInvoice = value.invoice_type === 'purchase' && value.direction === 'inbound';
    const hasBalanceDue = Number(value.balance_due ?? 0) > 0;

    const handleApprove = async () => {
        if (!value.row_version) return;
        setActionBusy(true);
        setActionError(null);
        try {
            await approveInvoice(id, value.row_version);
            invoice.reload();
        } catch (err) {
            setActionError(toApiError(err));
        } finally {
            setActionBusy(false);
        }
    };

    const handlePost = async () => {
        if (!value.row_version) return;
        setActionBusy(true);
        setActionError(null);
        try {
            await postInvoice(id, value.row_version);
            invoice.reload();
        } catch (err) {
            setActionError(toApiError(err));
        } finally {
            setActionBusy(false);
        }
    };

    return (
        <>
            <ContentHeader
                title={value.invoice_number ?? 'Invoice'}
                description={formatDate(value.invoice_date)}
                actions={(
                    <div className="flex flex-wrap justify-end gap-2">
                        {isRental && (
                            <Button
                                variant="secondary"
                                onClick={() => tabState.openTab('rental_invoice')}
                            >
                                View / Print Rental Invoice
                            </Button>
                        )}
                        {value.status === 'draft' && canApprove && (
                            <Button
                                variant="secondary"
                                onClick={handleApprove}
                                disabled={actionBusy}
                            >
                                Approve
                            </Button>
                        )}
                        {value.status === 'approved' && canPost && (
                            <Button
                                variant="secondary"
                                onClick={handlePost}
                                disabled={actionBusy}
                            >
                                Post Invoice
                            </Button>
                        )}
                        {value.status !== 'cancelled' && value.status !== 'void' && hasBalanceDue && (
                            value.direction === 'outbound' ? (
                                <LinkButton to={`/payments/create?party_id=${value.party?.id ?? ''}&invoice_id=${id}`}>
                                    Receive Payment
                                </LinkButton>
                            ) : (
                                <LinkButton to={`/purchase/payments/create?invoice_id=${id}`}>
                                    Create Payment
                                </LinkButton>
                            )
                        )}
                        {fromPurchase && (
                            <LinkButton to="/purchase/invoices" variant="secondary">Back to Purchase</LinkButton>
                        )}
                    </div>
                )}
            />
            {actionError && <div className="mb-4"><ErrorAlert error={actionError} /></div>}
            <Panel className="p-0">
                <Tabs tabs={tabs} active={tabState.activeTab} onChange={tabState.openTab} />
                <div className="p-5">
                    {tabState.activeTab === 'summary' && <DetailGrid items={[
                        { label: 'Status', value: <StatusBadge status={value.status} /> },
                        { label: 'Party', value: readableRelation(value.party) },
                        { label: 'Type', value: humanize(value.invoice_type) },
                        { label: 'Direction', value: humanize(value.direction) },
                        { label: 'Due date', value: formatDate(value.due_date) },
                        { label: 'Total', value: <MoneyDisplay value={value.grand_total} /> },
                        { label: 'Paid', value: <MoneyDisplay value={value.paid_total} /> },
                        { label: 'Credits', value: <MoneyDisplay value={value.credit_total} /> },
                        { label: 'Balance due', value: <MoneyDisplay value={value.balance_due} /> },
                    ]} />}
                    {tabState.activeTab === 'rental_invoice' && <RentalInvoicePrintView invoice={value} />}
                    {tabState.activeTab === 'lines' && <RecordTable rows={value.lines ?? []} fields={['line_number', 'item', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_amount', 'charge_amount', 'line_total']} rowKey={(row, index) => String(row.id ?? row.line_number ?? `invoice-line-${index}`)} />}
                    {canViewBalance && tabState.activeTab === 'balance' && <BalanceSummary loading={balance.loading} error={balance.error} balance={balance.data} />}
                    {canViewSources && tabState.activeTab === 'adjustments' && <AdjustmentRecords loading={adjustments.loading} error={adjustments.error} rows={adjustments.data ?? []} />}
                    {canViewSources && tabState.activeTab === 'sources' && <SourceRecords loading={sources.loading} error={sources.error} data={sources.data} />}
                </div>
            </Panel>
        </>
    );
}

function BalanceSummary({ loading, error, balance }: {
    loading: boolean;
    error: import('@/shared/api/apiError').ApiError | null;
    balance: import('../invoiceTypes').InvoiceBalanceResult | null;
}) {
    if (loading) return <LoadingState />;
    if (error) return <ErrorAlert error={error} />;
    if (!balance) return null;
    return <DetailGrid items={[
        { label: 'Invoice total', value: <MoneyDisplay value={balance.invoiceTotal} /> },
        { label: 'Payments', value: <MoneyDisplay value={balance.paidAmount} /> },
        { label: 'Credits and debit notes', value: <MoneyDisplay value={balance.creditAmount} /> },
        { label: 'Debit adjustments', value: <MoneyDisplay value={balance.debitAmount} /> },
        { label: 'Refunded', value: <MoneyDisplay value={balance.refundedAmount} /> },
        { label: 'Remaining', value: <MoneyDisplay value={balance.remainingAmount} /> },
        { label: 'Balance status', value: <StatusBadge status={balance.status} /> },
    ]} />;
}

function SourceRecords({ loading, error, data }: {
    loading: boolean;
    error: import('@/shared/api/apiError').ApiError | null;
    data: import('../invoiceTypes').InvoiceSourcesResult | null;
}) {
    if (loading) return <LoadingState />;
    if (error) return <ErrorAlert error={error} />;
    return (
        <div className="space-y-6">
            <section>
                <h3 className="mb-2 font-semibold text-slate-900">Source documents</h3>
                <RecordTable rows={data?.sources ?? []} fields={['source_type', 'source_document_number', 'source_document_date', 'source_subtotal', 'source_adjustment_total', 'source_grand_total', 'invoiced_amount']} rowKey={(row, index) => String(row.id ?? `${String(row.source_type ?? 'source')}-${String(row.source_document_number ?? index)}`)} />
            </section>
            <section>
                <h3 className="mb-2 font-semibold text-slate-900">Source lines</h3>
                <RecordTable rows={data?.source_lines ?? []} fields={['source_line_type', 'source_quantity', 'previously_invoiced_quantity', 'invoiced_quantity', 'remaining_quantity', 'invoiced_line_total']} rowKey={(row, index) => String(row.id ?? row.source_line_id ?? `${String(row.source_line_type ?? 'source-line')}-${index}`)} />
            </section>
        </div>
    );
}

function AdjustmentRecords({ loading, error, rows }: {
    loading: boolean;
    error: import('@/shared/api/apiError').ApiError | null;
    rows: import('../invoiceTypes').InvoiceAdjustment[];
}) {
    if (loading) return <LoadingState />;
    if (error) return <ErrorAlert error={error} />;
    const allocations = rows.flatMap((row) => row.allocations ?? []);
    return (
        <div className="space-y-6">
            <RecordTable rows={rows} fields={['name', 'adjustment_type', 'effect', 'calculation_type', 'rate', 'amount', 'allocation_method']} rowKey={(row, index) => String(row.id ?? `${String(row.name ?? 'adjustment')}-${index}`)} />
            {allocations.length > 0 && (
                <section>
                    <h3 className="mb-2 font-semibold text-slate-900">Allocation trace</h3>
                    <RecordTable rows={allocations} fields={['source_type', 'allocation_method', 'source_amount', 'previously_allocated_amount', 'allocated_amount', 'remaining_amount']} rowKey={(row, index) => String(row.id ?? `${String(row.source_type ?? 'allocation')}-${index}`)} />
                </section>
            )}
        </div>
    );
}
