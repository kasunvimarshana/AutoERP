import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { listPayments, type Payment } from '../paymentApi';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Input } from '@/shared/components/Input';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { formatDate } from '@/shared/utils/formatDate';
import { humanize, readableRelation } from '@/shared/utils/object';
import { Button } from '@/shared/components/Button';

export default function PaymentListPage() {
    const [searchParams] = useSearchParams();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const scope = useMemo(() => ({
        payment_type: searchParams.get('payment_type') || undefined,
        direction: searchParams.get('direction') || undefined,
        source_type: searchParams.get('source_type') || undefined,
    }), [searchParams]);
    const context = paymentContext(scope.payment_type, scope.direction, scope.source_type);
    const detailSearch = searchParams.toString();
    const result = useApi((signal) => listPayments({
        search: debounced || undefined,
        ...scope,
        page,
        per_page: 25,
    }, signal), [debounced, page, scope]);
    useEffect(() => {
        setSearch('');
        setPage(1);
    }, [scope.payment_type, scope.direction, scope.source_type]);
    const columns: DataColumn<Payment>[] = [
        { key: 'payment', header: 'Payment', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/payments/${row.id}${detailSearch ? `?${detailSearch}` : ''}`}>{row.payment_number ?? 'Payment'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.payment_date) },
        { key: 'party', header: 'Party', render: (row) => readableRelation(row.party) },
        { key: 'type', header: 'Type', render: (row) => `${humanize(row.payment_type)} / ${humanize(row.direction)}` },
        { key: 'total', header: 'Amount', render: (row) => <MoneyDisplay value={row.total_amount} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];
    return (
        <>
            <ContentHeader title={context.title} description={context.description} actions={!context.scoped ? <Link to="/payments/create"><Button>New payment</Button></Link> : undefined} />
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search payment or reference number" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage={`No ${context.title.toLowerCase()} found for this scope.`} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

function paymentContext(paymentType?: string, direction?: string, sourceType?: string) {
    if (sourceType === 'VehicleRentalAgreement') return { title: 'Rental Settlements', description: 'Receipts, supplier settlements, advances, and refunds linked to rental agreements.', scoped: true };
    if (paymentType === 'customer_receipt') return { title: 'Customer Receipts', description: 'Inbound customer receipts created from Sales.', scoped: true };
    if (paymentType === 'supplier_payment') return { title: 'Supplier Payments', description: 'Outbound payments to suppliers and owners.', scoped: true };
    if (paymentType === 'service_receipt') return { title: 'Vehicle Service Receipts', description: 'Inbound receipts allocated to vehicle service invoices.', scoped: true };
    if (direction === 'inbound') return { title: 'Inbound Payments', description: 'Receipts from every operational module.', scoped: true };
    if (direction === 'outbound') return { title: 'Outbound Payments', description: 'Payments issued across every operational module.', scoped: true };
    return { title: 'Payments', description: 'Consolidated payment activity with on-demand allocation data.', scoped: false };
}
