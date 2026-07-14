import { useState } from 'react';
import { Link } from 'react-router-dom';
import { listPayments, type Payment } from '@/modules/payment/paymentApi';
import { LinkButton } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useAuth } from '@/modules/auth/AuthProvider';
import { formatDate } from '@/shared/utils/formatDate';
import { humanize, readableRelation } from '@/shared/utils/object';
import { PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';

export default function PurchasePaymentWorkspacePage() {
    const auth = useAuth();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listPayments({
        search: debounced || undefined,
        payment_type: 'supplier_payment',
        direction: 'outbound',
        page,
        per_page: 25,
    }, signal), [debounced, page]);
    const canCreatePayment = hasPurchasePermission(auth, purchasePermissions.paymentsExecute);

    const columns: DataColumn<Payment>[] = [
        { key: 'payment', header: 'Payment', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/payments/${row.id}?from=purchase`}>{row.payment_number ?? 'Payment number unavailable'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.payment_date) },
        { key: 'party', header: 'Supplier', render: (row) => readableRelation(row.party) },
        { key: 'type', header: 'Type', render: (row) => `${humanize(row.payment_type)} / ${humanize(row.direction)}` },
        { key: 'total', header: 'Amount', render: (row) => <MoneyDisplay value={row.total_amount} /> },
        { key: 'allocated', header: 'Allocated', render: (row) => <MoneyDisplay value={row.allocated_amount} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.document_status} /> },
    ];

    return (
        <div className="space-y-5">
            <PurchasePageHeader
                title="Supplier Payments"
                description="Outbound payments created for supplier invoices from the Purchase workflow."
                actions={canCreatePayment ? <LinkButton to="/purchase/payments/create">Create Supplier Payment</LinkButton> : undefined}
            />
            <div className="max-w-md">
                <Input type="search" placeholder="Search payment or reference number" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading
                ? <LoadingState />
                : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} rowHref={(row) => `/payments/${row.id}?from=purchase`} emptyMessage="No supplier payments found." />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </div>
    );
}
