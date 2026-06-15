import { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
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
import { humanize } from '@/shared/utils/object';
import { listVouchers, type VoucherListRow } from '../voucherApi';

const typeOptions = [
    { value: 'receipt_voucher', label: 'Receipt Vouchers' },
    { value: 'payment_voucher', label: 'Payment Vouchers' },
    { value: 'journal_voucher', label: 'Journal Vouchers' },
    { value: 'contra_voucher', label: 'Contra Vouchers' },
    { value: 'adjustment_voucher', label: 'Adjustment Vouchers' },
    { value: 'opening_balance_voucher', label: 'Opening Vouchers' },
    { value: 'reversal_voucher', label: 'Reversals' },
];

export default function VoucherListPage() {
    const [params, setParams] = useSearchParams();
    const [search, setSearch] = useState(params.get('search') ?? '');
    const page = Number(params.get('page') ?? '1');
    const voucherType = params.get('voucher_type') ?? '';
    const documentStatus = params.get('document_status') ?? '';

    const requestParams = useMemo(() => ({
        page,
        per_page: 25,
        search: params.get('search') || undefined,
        voucher_type: voucherType || undefined,
        document_status: documentStatus || undefined,
        date_from: params.get('date_from') || undefined,
        date_to: params.get('date_to') || undefined,
    }), [documentStatus, page, params, voucherType]);

    const vouchers = useApi((signal) => listVouchers(requestParams, signal), [requestParams], true, true);

    const setFilter = (key: string, value: string) => {
        const next = new URLSearchParams(params);
        if (value) next.set(key, value);
        else next.delete(key);
        if (key !== 'page') next.set('page', '1');
        setParams(next);
    };

    const columns: Array<DataColumn<VoucherListRow>> = [
        {
            key: 'number',
            header: 'Voucher',
            render: (row) => (
                <div className="min-w-44">
                    <Link className="font-semibold text-sky-700 hover:text-sky-900" to={row.voucher_url ?? `/vouchers/${row.voucher_type}/${row.source_id}?source_kind=${row.source_kind ?? ''}`}>
                        {row.voucher_number}
                    </Link>
                    <div className="text-xs text-slate-500">{row.voucher_label ?? humanize(row.voucher_type)}</div>
                </div>
            ),
        },
        { key: 'date', header: 'Date', render: (row) => row.voucher_date ?? '-' },
        { key: 'source', header: 'Source', render: (row) => <span>{row.source_module} {row.source_document_number ? `/ ${row.source_document_number}` : ''}</span> },
        { key: 'party', header: 'Party / Narration', render: (row) => row.party_or_narration ?? '-' },
        { key: 'amount', header: 'Amount', className: 'text-right', render: (row) => <MoneyDisplay value={row.transaction_amount} currency={row.currency_code ?? undefined} /> },
        { key: 'document_status', header: 'Document', render: (row) => <StatusBadge status={row.document_status} /> },
        { key: 'posting_status', header: 'Posting', render: (row) => <StatusBadge status={row.posting_status} /> },
        { key: 'actions', header: '', className: 'text-right', mobile: false, render: (row) => <Link className="text-sm font-semibold text-sky-700" to={row.voucher_url ?? `/vouchers/${row.voucher_type}/${row.source_id}?source_kind=${row.source_kind ?? ''}`}>Open</Link> },
    ];

    return (
        <div className="space-y-5">
            <ContentHeader
                title="Vouchers"
                actions={<>
                    <Link className="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" to="/payments/create">New Receipt / Payment</Link>
                    <Link className="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" to="/finance/journals/create">New Journal</Link>
                </>}
            />

            <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-5">
                <form
                    className="md:col-span-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        setFilter('search', search);
                    }}
                >
                    <Input label="Search" value={search} onChange={(event) => setSearch(event.target.value)} />
                </form>
                <Select label="Type" value={voucherType} options={typeOptions} onChange={(event) => setFilter('voucher_type', event.target.value)} />
                <Select label="Document" value={documentStatus} options={[
                    { value: 'draft', label: 'Draft' },
                    { value: 'submitted', label: 'Submitted' },
                    { value: 'approved', label: 'Approved' },
                    { value: 'voided', label: 'Voided' },
                    { value: 'reversed', label: 'Reversed' },
                ]} onChange={(event) => setFilter('document_status', event.target.value)} />
                <Button className="self-end" onClick={() => setFilter('search', search)}>Apply</Button>
            </div>

            <ErrorAlert error={vouchers.error} />
            {vouchers.loading ? <LoadingState /> : (
                <>
                    <DataTable
                        rows={vouchers.data?.data ?? []}
                        columns={columns}
                        rowKey={(row) => `${row.voucher_type}-${row.source_kind}-${row.source_id}`}
                        emptyMessage="No vouchers found."
                        mobileSummary={(row) => row.voucher_number ?? '-'}
                        rowBadge={(row) => <StatusBadge status={row.document_status} />}
                        mobileActions={(row) => <Link className="text-sm font-semibold text-sky-700" to={row.voucher_url ?? `/vouchers/${row.voucher_type}/${row.source_id}?source_kind=${row.source_kind ?? ''}`}>Open</Link>}
                    />
                    <Pagination meta={vouchers.data?.meta} onPageChange={(nextPage) => setFilter('page', String(nextPage))} />
                </>
            )}
        </div>
    );
}
