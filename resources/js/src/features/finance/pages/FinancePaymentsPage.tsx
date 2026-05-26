import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useTenant } from '../../auth/context/TenantContext';
import { usePayments } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatCurrency, formatDate, parsePositiveInteger } from '../../shared/utils';
import type { PaymentRecord } from '../types';

export function FinancePaymentsPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const direction = searchParams.get('direction') ?? '';
    const status = searchParams.get('status') ?? '';

    const paymentsQuery = usePayments({
        tenant_id: tenantId,
        page,
        per_page: 10,
        direction: direction ? (direction as 'inbound' | 'outbound') : undefined,
        status: status || undefined,
        sort: '-payment_date',
    });

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            if ('direction' in updates || 'status' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns: DataTableColumn<PaymentRecord>[] = useMemo(
        () => [
            {
                key: 'payment_number',
                header: 'Payment',
                render: (payment) => (
                    <div>
                        <p className="font-medium text-stone-950">{payment.payment_number}</p>
                        <p className="mt-1 text-xs text-stone-500">{payment.reference || `Party ${payment.party_type} #${payment.party_id ?? '-'}`}</p>
                    </div>
                ),
            },
            { key: 'direction', header: 'Direction', render: (payment) => <StatusBadge>{payment.direction}</StatusBadge> },
            { key: 'party_type', header: 'Party', render: (payment) => <span className="text-sm text-stone-700">{`${payment.party_type} #${payment.party_id ?? '-'}`}</span> },
            { key: 'amount', header: 'Amount', render: (payment) => <span className="text-sm text-stone-700">{formatCurrency(payment.base_amount)}</span> },
            { key: 'status', header: 'Status', render: (payment) => <StatusBadge tone={payment.status === 'posted' || payment.status === 'reconciled' ? 'success' : 'default'}>{payment.status}</StatusBadge> },
            { key: 'payment_date', header: 'Payment Date', render: (payment) => <span className="text-sm text-stone-700">{formatDate(payment.payment_date)}</span> },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Finance' }, { label: 'Payments' }]}
                description="Payment visibility now uses the live finance payment endpoint, keeping the Phase 5 payment route aligned with the existing API client and tenant headers."
                title="Payments"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Review inbound and outbound payments, reconcile posting status, and inspect party-level flow at a glance." title="Payment register">
                    <SearchFilterToolbar
                        filters={
                            <>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ direction: event.target.value || undefined })} value={direction}>
                                    <option value="">All directions</option>
                                    <option value="inbound">Inbound</option>
                                    <option value="outbound">Outbound</option>
                                </Select>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}>
                                    <option value="">All statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="posted">Posted</option>
                                    <option value="reconciled">Reconciled</option>
                                    <option value="voided">Voided</option>
                                </Select>
                            </>
                        }
                        trailing={<div className="text-sm text-stone-500">{paymentsQuery.data?.meta?.total ?? 0} payments</div>}
                    />
                </TableToolbar>

                {paymentsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : paymentsQuery.isError ? (
                    isForbiddenError(paymentsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={paymentsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={paymentsQuery.error.message} title="Unable to load payments" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No payments match the current filters." title="No payments found" />}
                        footer={<TablePagination meta={paymentsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(payment) => payment.id}
                        rows={paymentsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
