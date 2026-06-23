import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import type { NamedResource } from '@/shared/types/common';
import { compareDecimalStrings } from '@/shared/utils/decimal';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import {
    allocatePurchaseDebitNote,
    approvePurchaseDebitNote,
    getPurchaseDebitNote,
    postPurchaseDebitNote,
} from '../purchaseApi';
import { PurchaseInvoiceLookupSelect } from '../components/PurchaseLookups';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';

export default function PurchaseDebitNoteDetailPage() {
    const { confirm, confirmDialog } = useConfirmDialog();
    const id = Number(useParams().id);
    const auth = useAuth();
    const result = useApi((signal) => getPurchaseDebitNote(id, signal), [id]);
    const [busy, setBusy] = useState(false);
    const [allocationOpen, setAllocationOpen] = useState(false);
    const [invoice, setInvoice] = useState<NamedResource | null>(null);
    const [amount, setAmount] = useState('0.000000');
    const [error, setError] = useState<ApiError | null>(null);
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const note = result.data;
    const capabilities = note.capabilities ?? {};
    const can = (permission: string) => hasPurchasePermission(auth.permissions, permission);
    return (
        <>
            <PurchaseDocumentShell
            header={<PurchasePageHeader
                title={note.debit_note_number ?? 'Debit note'}
                description={formatDate(note.debit_note_date)}
                actions={<div className="flex gap-2">
                    {capabilities.can_approve && can(purchasePermissions.debitNotesApprove) && <Button loading={busy} onClick={() => void runAction('approve')}>Approve</Button>}
                    {capabilities.can_post && can(purchasePermissions.debitNotesPost) && <Button loading={busy} onClick={() => void runAction('post')}>Post</Button>}
                    {capabilities.can_allocate && can(purchasePermissions.debitNotesAllocate) && compareDecimalStrings(note.remaining_amount ?? '0', '0') > 0 && <Button disabled={busy} onClick={() => {
                        setError(null);
                        setInvoice(null);
                        setAmount(note.remaining_amount ?? '0.000000');
                        setAllocationOpen(true);
                    }}>Allocate</Button>}
                </div>}
            />}
        >
            <ErrorAlert error={error ?? result.error} />
            <Panel>
                <DetailGrid items={[
                    { label: 'Workflow', value: <StatusBadge status={note.status} /> },
                    { label: 'Allocation', value: note.allocation_status?.replaceAll('_', ' ') ?? '-' },
                    { label: 'Supplier', value: note.supplier?.name ?? '-' },
                    { label: 'Amount', value: formatMoney(note.amount) },
                    { label: 'Allocated', value: formatMoney(note.allocated_amount) },
                    { label: 'Remaining', value: formatMoney(note.remaining_amount) },
                    { label: 'Source', value: note.source?.number ?? note.source?.type?.replaceAll('_', ' ') ?? '-' },
                    { label: 'Purchase return', value: note.purchase_return_id ? <Link className="text-sky-700 hover:underline" to={`/purchase/returns/${note.purchase_return_id}`}>{note.purchase_return?.return_number ?? note.source?.number ?? 'Purchase return'}</Link> : '-' },
                    { label: 'Reason', value: note.reason ?? '-' },
                ]} />
            </Panel>
            <FormDrawer open={allocationOpen} title="Allocate debit note" onClose={closeAllocation}>
                <form className="space-y-4" onSubmit={async (event) => {
                    event.preventDefault();
                    if (!invoice || busy) return;
                    setBusy(true);
                    setError(null);
                    try {
                        const updated = await allocatePurchaseDebitNote(note.id, {
                            invoice_id: invoice.id,
                            amount,
                        });
                        result.setData(updated);
                        setAllocationOpen(false);
                        setInvoice(null);
                        setAmount('0.000000');
                    } catch (requestError) {
                        setError(toApiError(requestError));
                    } finally {
                        setBusy(false);
                    }
                }}>
                    <ErrorAlert error={error} />
                    <PurchaseInvoiceLookupSelect
                        partyId={note.supplier?.id}
                        value={invoice}
                        onChange={setInvoice}
                        error={fieldError(error, 'invoice_id')}
                    />
                    <DecimalInput
                        label="Allocation amount"
                        value={amount}
                        error={fieldError(error, 'amount')}
                        onChange={(event) => setAmount(event.target.value)}
                    />
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" onClick={closeAllocation}>Cancel</Button>
                        <Button type="submit" loading={busy}>Allocate</Button>
                    </div>
                </form>
            </FormDrawer>
            </PurchaseDocumentShell>
            {confirmDialog}
        </>
    );

    async function runAction(action: 'approve' | 'post') {
        if (busy) return;
        if (!await confirm({
            title: `${action[0].toUpperCase()}${action.slice(1)} debit note`,
            message: `Confirm ${action} for this debit note?`,
            confirmLabel: action[0].toUpperCase() + action.slice(1),
            danger: false,
        })) return;
        setBusy(true);
        setError(null);
        try {
            const updated = action === 'approve'
                ? await approvePurchaseDebitNote(note.id)
                : await postPurchaseDebitNote(note.id);
            result.setData(updated);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    function closeAllocation() {
        if (busy) return;
        setAllocationOpen(false);
        setInvoice(null);
        setAmount('0.000000');
        setError(null);
    }
}
