import { Link, useParams, useSearchParams } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { humanize } from '@/shared/utils/object';
import { openSameOriginUrl } from '@/shared/utils/safeNavigation';
import { getVoucher, type VoucherAllocation, type VoucherJournalLine, type VoucherLine } from '../voucherApi';
import { VoucherStatusStrip } from '../components/VoucherStatusStrip';

export default function VoucherDetailPage() {
    const { voucherType = '', sourceId = '' } = useParams();
    const [searchParams] = useSearchParams();
    const numericSourceId = Number(sourceId);
    const sourceKind = searchParams.get('source_kind');
    const voucher = useApi((signal) => getVoucher(voucherType, numericSourceId, sourceKind, signal), [voucherType, numericSourceId, sourceKind], Boolean(voucherType && numericSourceId), true);

    if (voucher.loading) return <LoadingState />;
    if (voucher.error) return <ErrorAlert error={voucher.error} />;
    if (!voucher.data) return null;

    const paymentColumns: Array<DataColumn<VoucherLine>> = [
        { key: 'method', header: 'Method', render: (row) => row.method ?? humanize(row.method_type) },
        { key: 'instrument', header: 'Instrument', render: (row) => row.instrument_number ?? row.reference_number ?? '-' },
        { key: 'bank', header: 'Bank', render: (row) => row.external_bank ?? row.internal_bank_account ?? '-' },
        { key: 'amount', header: 'Amount', className: 'text-right', render: (row) => <MoneyDisplay value={row.amount} currency={voucher.data?.currency ?? undefined} /> },
        { key: 'status', header: 'Status', render: (row) => humanize(row.status) },
    ];

    const allocationColumns: Array<DataColumn<VoucherAllocation>> = [
        { key: 'document', header: 'Document', render: (row) => row.invoice_number ?? '-' },
        { key: 'date', header: 'Date', render: (row) => row.allocation_date ?? row.invoice_date ?? '-' },
        { key: 'allocated', header: 'Allocated', className: 'text-right', render: (row) => <MoneyDisplay value={row.allocated_amount} currency={voucher.data?.currency ?? undefined} /> },
        { key: 'balance', header: 'Balance After', className: 'text-right', render: (row) => <MoneyDisplay value={row.invoice_balance_after} currency={voucher.data?.currency ?? undefined} /> },
        { key: 'status', header: 'Status', render: (row) => humanize(row.status) },
    ];

    const journalColumns: Array<DataColumn<VoucherJournalLine>> = [
        { key: 'account', header: 'Account', render: (row) => `${row.account_code ?? ''} ${row.account_name ?? ''}`.trim() || '-' },
        { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
        { key: 'debit', header: 'Debit', className: 'text-right', render: (row) => <MoneyDisplay value={row.debit} currency={voucher.data?.currency ?? undefined} /> },
        { key: 'credit', header: 'Credit', className: 'text-right', render: (row) => <MoneyDisplay value={row.credit} currency={voucher.data?.currency ?? undefined} /> },
    ];

    return (
        <div className="space-y-5">
            <ContentHeader
                title={voucher.data.voucher_number ?? humanize(voucher.data.voucher_type)}
                actions={<>
                    {voucher.data.source_document_url && <Link className="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" to={voucher.data.source_document_url}>Source</Link>}
                    {voucher.data.print_url && <Button variant="secondary" onClick={() => openSameOriginUrl(voucher.data?.print_url ?? '')}>Print</Button>}
                </>}
            />

            <Panel>
                <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div className="text-lg font-semibold text-slate-900">{voucher.data.voucher_label ?? humanize(voucher.data.voucher_type)}</div>
                        <div className="text-sm text-slate-500">{voucher.data.source_module} / {voucher.data.source_document_number}</div>
                    </div>
                    <VoucherStatusStrip
                        document={voucher.data.document_status}
                        allocation={voucher.data.allocation_status}
                        posting={voucher.data.posting_status}
                        instrument={voucher.data.instrument_status}
                    />
                </div>
                <DetailGrid items={[
                    { label: 'Date', value: voucher.data.voucher_date },
                    { label: 'Party', value: voucher.data.payer_or_payee ?? voucher.data.party_name ?? '-' },
                    { label: 'Currency', value: `${voucher.data.currency ?? '-'} @ ${voucher.data.exchange_rate ?? '-'}` },
                    { label: 'Amount', value: <MoneyDisplay value={voucher.data.transaction_amount} currency={voucher.data.currency ?? undefined} /> },
                    { label: 'Allocated', value: <MoneyDisplay value={voucher.data.allocated_amount} currency={voucher.data.currency ?? undefined} /> },
                    { label: 'Unallocated', value: <MoneyDisplay value={voucher.data.unallocated_amount} currency={voucher.data.currency ?? undefined} /> },
                ]} />
            </Panel>

            {voucher.data.narration && <Panel title="Narration"><p className="text-sm text-slate-700">{voucher.data.narration}</p></Panel>}
            {(voucher.data.payment_lines?.length ?? 0) > 0 && <Panel title="Payment Instruments"><DataTable rows={voucher.data.payment_lines ?? []} columns={paymentColumns} rowKey={(row) => `${row.reference_number ?? row.instrument_number ?? row.method ?? 'line'}-${row.amount ?? ''}`} /></Panel>}
            {(voucher.data.invoice_or_payable_references?.length ?? 0) > 0 && <Panel title="Allocations"><DataTable rows={voucher.data.invoice_or_payable_references ?? []} columns={allocationColumns} rowKey={(row) => `${row.invoice_number ?? 'allocation'}-${row.allocated_amount ?? ''}`} /></Panel>}
            {(voucher.data.journal_lines?.length ?? 0) > 0 && <Panel title="Journal Lines"><DataTable rows={voucher.data.journal_lines ?? []} columns={journalColumns} rowKey={(row) => `${row.line_number ?? row.account_code ?? 'journal'}-${row.debit ?? ''}-${row.credit ?? ''}`} /></Panel>}
        </div>
    );
}
