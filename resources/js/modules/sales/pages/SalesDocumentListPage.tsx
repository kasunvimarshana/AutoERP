import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { NamedResource } from '@/shared/types/common';
import type { ApiCollection } from '@/shared/types/api';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import {
    acceptSalesQuotation,
    approveSalesOrder,
    cancelSalesOrder,
    closeSalesOrder,
    convertSalesQuotation,
    listSalesOrders,
    listSalesQuotations,
    rejectSalesQuotation,
    sendSalesQuotation,
    submitSalesOrder,
} from '../salesApi';
import type { SalesOrder, SalesQuotation } from '../salesTypes';
import { CustomerLookupSelect } from '../components/SalesLookups';

type Kind = 'quotation' | 'order';
type Row = SalesQuotation | SalesOrder;

const quotationStatuses = ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted', 'cancelled'];
const orderStatuses = ['draft', 'pending_approval', 'approved', 'partially_allocated', 'allocated', 'partially_delivered', 'delivered', 'partially_invoiced', 'invoiced', 'closed', 'cancelled'];

export default function SalesDocumentListPage({ kind }: { kind: Kind }) {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [customer, setCustomer] = useState<NamedResource | null>(null);
    const [page, setPage] = useState(1);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi<ApiCollection<Row>>(
        (signal): Promise<ApiCollection<Row>> => kind === 'quotation'
            ? listSalesQuotations({ search: debounced || undefined, status: status || undefined, customer_id: customer?.id, page, per_page: 25 }, signal)
            : listSalesOrders({ search: debounced || undefined, status: status || undefined, customer_id: customer?.id, page, per_page: 25 }, signal),
        [kind, debounced, status, customer?.id, page],
    );
    const segment = kind === 'quotation' ? 'quotations' : 'orders';

    const runAction = async (row: Row, action: string) => {
        setBusyId(row.id);
        setActionError(null);
        try {
            if (kind === 'quotation') {
                if (action === 'send') await sendSalesQuotation(row.id);
                if (action === 'accept') await acceptSalesQuotation(row.id);
                if (action === 'reject') await rejectSalesQuotation(row.id);
                if (action === 'convert') {
                    const order = await convertSalesQuotation(row.id, {
                        sales_order_date: businessDateInputValue(),
                    });
                    navigate(`/sales/orders/${order.id}`);
                    return;
                }
            } else {
                if (action === 'submit') await submitSalesOrder(row.id);
                if (action === 'approve') await approveSalesOrder(row.id);
                if (action === 'cancel') await cancelSalesOrder(row.id);
                if (action === 'close') await closeSalesOrder(row.id);
            }
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const columns: DataColumn<Row>[] = [
        {
            key: 'number',
            header: kind === 'quotation' ? 'Quotation' : 'Order',
            render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/sales/${segment}/${row.id}`}>{numberFor(row, kind)}</Link>,
        },
        { key: 'date', header: 'Date', render: (row) => formatDate(dateFor(row, kind)) },
        { key: 'customer', header: 'Customer', render: (row) => readableRelation(row.customer) },
        { key: 'total', header: 'Total', render: (row) => <MoneyDisplay value={row.grand_total} currency={row.currency?.code ?? undefined} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <LinkButton to={`/sales/${segment}/${row.id}`} variant="ghost">View</LinkButton>
                    {row.status === 'draft' && <LinkButton to={`/sales/${segment}/${row.id}/edit`} variant="secondary">Edit</LinkButton>}
                    {kind === 'quotation' && row.status === 'draft' && <Button type="button" loading={busyId === row.id} onClick={() => runAction(row, 'send')}>Send</Button>}
                    {kind === 'quotation' && row.status === 'sent' && <Button type="button" loading={busyId === row.id} onClick={() => runAction(row, 'accept')}>Accept</Button>}
                    {kind === 'quotation' && row.status === 'sent' && <Button type="button" variant="danger" loading={busyId === row.id} onClick={() => runAction(row, 'reject')}>Reject</Button>}
                    {kind === 'quotation' && row.status === 'accepted' && <Button type="button" loading={busyId === row.id} onClick={() => runAction(row, 'convert')}>Convert to order</Button>}
                    {kind === 'order' && row.status === 'draft' && <Button type="button" loading={busyId === row.id} onClick={() => runAction(row, 'submit')}>Submit</Button>}
                    {kind === 'order' && row.status === 'pending_approval' && <Button type="button" loading={busyId === row.id} onClick={() => runAction(row, 'approve')}>Approve</Button>}
                    {kind === 'order' && ['draft', 'pending_approval', 'approved'].includes(row.status ?? '') && <Button type="button" variant="danger" loading={busyId === row.id} onClick={() => runAction(row, 'cancel')}>Cancel</Button>}
                    {kind === 'order' && ['approved', 'delivered', 'invoiced'].includes(row.status ?? '') && <Button type="button" variant="secondary" loading={busyId === row.id} onClick={() => runAction(row, 'close')}>Close</Button>}
                </div>
            ),
        },
    ];

    return (
        <>
            <ContentHeader
                title={kind === 'quotation' ? 'Sales quotations' : 'Sales orders'}
                description={kind === 'quotation' ? 'Quote, send, accept, and convert customer proposals.' : 'Approve and track customer demand through delivery and invoicing.'}
                actions={<LinkButton to={`/sales/${segment}/create`}>New {kind}</LinkButton>}
            />
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Input type="search" label="Search" value={search} placeholder="Document number or customer" onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={(kind === 'quotation' ? quotationStatuses : orderStatuses).map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <CustomerLookupSelect value={customer} onChange={(value) => { setCustomer(value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} mobileSummary={(row) => numberFor(row, kind)} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

function numberFor(row: Row, kind: Kind) {
    return kind === 'quotation'
        ? (row as SalesQuotation).quotation_number ?? 'Quotation'
        : (row as SalesOrder).sales_order_number ?? 'Sales order';
}

function dateFor(row: Row, kind: Kind) {
    return kind === 'quotation'
        ? (row as SalesQuotation).quotation_date
        : (row as SalesOrder).sales_order_date;
}
