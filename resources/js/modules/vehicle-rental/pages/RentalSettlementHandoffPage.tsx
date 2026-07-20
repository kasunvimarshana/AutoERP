import { useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { invoicePermissions } from '@/modules/invoice/invoicePermissions';
import { paymentPermissions } from '@/modules/payment/paymentPermissions';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import { listRentalCalculations } from '../vehicleRentalApi';
import type { RentalCalculation, RentalCalculationSide, RentalReference } from '../vehicleRentalTypes';

const PAGE_SIZE = 25;

interface RentalSettlementHandoffPageProps {
    side: RentalCalculationSide;
}

function relationLabel(value?: RentalReference | null): string {
    return value?.name || value?.code || '—';
}

export function RentalSettlementHandoffPage({ side }: RentalSettlementHandoffPageProps) {
    const auth = useAuth();
    const canCreatePayment = hasPermission(auth, paymentPermissions.create);
    const canViewPayments = hasPermission(auth, paymentPermissions.view);
    const canViewInvoice = hasPermission(auth, invoicePermissions.view);
    const [page, setPage] = useState(1);
    const customerSide = side === 'customer';
    const result = useApi(
        (signal) => listRentalCalculations({
            calculation_side: side,
            calculation_status: 'calculated',
            page,
            per_page: PAGE_SIZE,
        }, signal),
        [side, page],
    );
    const rows = (result.data?.data ?? []).filter((row) => row.financial_document !== null && row.financial_document !== undefined);
    const title = customerSide ? 'Customer receipts' : 'Owner payments';
    const description = customerSide
        ? 'Receive and allocate customer payments through the shared Payment module without duplicating receipt logic inside Vehicle Rental.'
        : 'Pay and allocate Owner Payable Vouchers through the shared Payment module without duplicating supplier-payment logic inside Vehicle Rental.';
    const actionLabel = customerSide ? 'New customer receipt' : 'New owner payment';

    const columns: DataColumn<RentalCalculation>[] = [
        { key: 'agreement', header: 'Agreement', render: (row) => relationLabel(row.agreement) },
        {
            key: 'document',
            header: customerSide ? 'Customer invoice' : 'Owner Payable Voucher',
            render: (row) => row.financial_document?.invoice_number ?? '—',
        },
        {
            key: 'total',
            header: 'Total',
            render: (row) => <MoneyDisplay value={row.financial_document?.grand_total} currency={row.financial_document?.currency?.code ?? undefined} />,
        },
        {
            key: 'outstanding',
            header: 'Outstanding',
            render: (row) => <MoneyDisplay value={row.financial_document?.balance_due} currency={row.financial_document?.currency?.code ?? undefined} />,
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => row.financial_document ? <StatusBadge status={row.financial_document.status} /> : null,
        },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => row.financial_document && canCreatePayment && isPositiveDecimal(row.financial_document.balance_due) ? (
                <LinkButton to={`/payments/create?invoice_id=${row.financial_document.id}`}>
                    {customerSide ? 'Record receipt' : 'Record payment'}
                </LinkButton>
            ) : row.financial_document && canViewInvoice ? (
                <LinkButton variant="secondary" to={`/invoices/${row.financial_document.id}`}>View document</LinkButton>
            ) : null,
        },
    ];

    return (
        <>
            <ContentHeader
                title={title}
                description={description}
                actions={canCreatePayment ? <LinkButton to="/payments/create">{actionLabel}</LinkButton> : undefined}
            />
            <Panel title="Shared settlement workspace">
                <p className="text-sm text-slate-700">
                    Payment methods, cheque details, allocations, overpayment protection, posting, and reversal remain owned by the Payment module.
                </p>
                <div className="mt-4 flex flex-wrap gap-2">
                    {canViewPayments && <LinkButton variant="secondary" to="/payments">View all payments</LinkButton>}
                    <LinkButton variant="secondary" to="/finance/bank-reconciliations">Bank reconciliation</LinkButton>
                </div>
            </Panel>
            <div className="mt-5">
                <ErrorAlert error={result.error} inline />
                {result.loading
                    ? <LoadingState />
                    : <DataTable rows={rows} columns={columns} rowKey={(row) => row.id} />}
                <Pagination meta={result.data?.meta} onPageChange={setPage} />
            </div>
        </>
    );
}
