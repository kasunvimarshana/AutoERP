import { useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormDrawer } from '@/shared/components/Drawer';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { NamedResource } from '@/shared/types/common';
import { compareDecimalStrings } from '@/shared/utils/decimal';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import {
    allocateSalesCreditNote,
    approveSalesCreditNote,
    createSalesCreditNote,
    listSalesCreditNotes,
    postSalesCreditNote,
} from '../salesApi';
import type { SalesCreditNote } from '../salesTypes';
import { CustomerLookupSelect, SalesInvoiceLookupSelect } from '../components/SalesLookups';

export default function SalesCreditNotePage() {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [open, setOpen] = useState(false);
    const [customer, setCustomer] = useState<NamedResource | null>(null);
    const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
    const [amount, setAmount] = useState('0.000000');
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [acting, setActing] = useState<string | null>(null);
    const [allocationNote, setAllocationNote] = useState<SalesCreditNote | null>(null);
    const [invoice, setInvoice] = useState<NamedResource | null>(null);
    const [allocationAmount, setAllocationAmount] = useState('0.000000');
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listSalesCreditNotes({ search: debounced || undefined, page, per_page: 25 }, signal), [debounced, page]);

    const columns: DataColumn<SalesCreditNote>[] = [
        { key: 'number', header: 'Credit note', render: (row) => row.credit_note_number ?? 'Credit note' },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.credit_note_date) },
        { key: 'customer', header: 'Customer', render: (row) => readableRelation(row.customer) },
        { key: 'amount', header: 'Amount', render: (row) => <MoneyDisplay value={row.amount} /> },
        { key: 'remaining', header: 'Remaining', render: (row) => <MoneyDisplay value={row.remaining_amount} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: 'Actions', render: (row) => (
            <div className="flex flex-wrap gap-2">
                {row.status === 'draft' && <Button
                    variant="secondary"
                    loading={acting === `${row.id}:approve`}
                    onClick={() => void runAction(row, 'approve')}
                >Approve</Button>}
                {row.status === 'approved' && <Button
                    variant="secondary"
                    loading={acting === `${row.id}:post`}
                    onClick={() => void runAction(row, 'post')}
                >Post</Button>}
                {row.status === 'posted' && compareDecimalStrings(row.remaining_amount ?? '0', '0') > 0 && <Button
                    variant="secondary"
                    onClick={() => {
                        setActionError(null);
                        setAllocationNote(row);
                        setInvoice(null);
                        setAllocationAmount(row.remaining_amount ?? '0.000000');
                    }}
                >Allocate</Button>}
            </div>
        ) },
    ];

    return (
        <>
            <ContentHeader title="Sales credit notes" description="Customer credit-note orchestration; Invoice owns allocation against customer invoices." actions={<Button onClick={() => {
                setActionError(null);
                setOpen(true);
            }}>New credit note</Button>} />
            <div className="mb-4"><Input type="search" label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} mobileSummary={(row) => row.credit_note_number ?? 'Credit note'} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <FormDrawer open={open} title="Create credit note" onClose={() => setOpen(false)}>
                <form className="space-y-4" onSubmit={async (event) => {
                    event.preventDefault();
                    if (!customer || submitting) return;
                    setSubmitting(true);
                    setActionError(null);
                    try {
                        await createSalesCreditNote({
                            credit_note_date: date,
                            customer_id: customer.id,
                            amount,
                            reason,
                        });
                        setOpen(false);
                        setCustomer(null);
                        setAmount('0.000000');
                        setReason('');
                        result.reload();
                    } catch (error) {
                        setActionError(toApiError(error));
                    } finally {
                        setSubmitting(false);
                    }
                }}>
                    <CustomerLookupSelect value={customer} onChange={setCustomer} error={fieldError(actionError, 'customer_id')} />
                    <Input type="date" label="Credit note date" value={date} error={fieldError(actionError, 'credit_note_date')} onChange={(event) => setDate(event.target.value)} />
                    <DecimalInput label="Amount" value={amount} error={fieldError(actionError, 'amount')} onChange={(event) => setAmount(event.target.value)} />
                    <Input label="Reason" value={reason} error={fieldError(actionError, 'reason')} onChange={(event) => setReason(event.target.value)} />
                    <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => setOpen(false)}>Cancel</Button><Button type="submit" loading={submitting}>Create</Button></div>
                </form>
            </FormDrawer>
            <FormDrawer open={allocationNote !== null} title="Allocate credit note" onClose={closeAllocation}>
                <form className="space-y-4" onSubmit={async (event) => {
                    event.preventDefault();
                    if (!allocationNote || !invoice || submitting) return;
                    setSubmitting(true);
                    setActionError(null);
                    try {
                        await allocateSalesCreditNote(allocationNote.id, {
                            invoice_id: invoice.id,
                            amount: allocationAmount,
                        });
                        setAllocationNote(null);
                        setInvoice(null);
                        setAllocationAmount('0.000000');
                        result.reload();
                    } catch (error) {
                        setActionError(toApiError(error));
                    } finally {
                        setSubmitting(false);
                    }
                }}>
                    <ErrorAlert error={actionError} />
                    <SalesInvoiceLookupSelect
                        partyId={allocationNote?.customer?.id}
                        value={invoice}
                        onChange={setInvoice}
                        error={fieldError(actionError, 'invoice_id')}
                    />
                    <DecimalInput
                        label="Allocation amount"
                        value={allocationAmount}
                        error={fieldError(actionError, 'amount')}
                        onChange={(event) => setAllocationAmount(event.target.value)}
                    />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={closeAllocation}>Cancel</Button>
                        <Button type="submit" loading={submitting}>Allocate</Button>
                    </div>
                </form>
            </FormDrawer>
        </>
    );

    async function runAction(note: SalesCreditNote, action: 'approve' | 'post') {
        const actionKey = `${note.id}:${action}`;
        if (acting) return;
        setActing(actionKey);
        setActionError(null);
        try {
            if (action === 'approve') {
                await approveSalesCreditNote(note.id);
            } else {
                await postSalesCreditNote(note.id);
            }
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setActing(null);
        }
    }

    function closeAllocation() {
        if (submitting) return;
        setAllocationNote(null);
        setInvoice(null);
        setAllocationAmount('0.000000');
        setActionError(null);
    }
}
