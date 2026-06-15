import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import { createRentalInvoice, getRentalAgreement, listRentalInvoiceCharges, previewRentalInvoice } from '../vehicleRentalApi';
import type { RentalInvoicePreview } from '../vehicleRentalTypes';

const today = businessDateInputValue;

export default function RentalInvoiceCreatePage() {
    const agreementId = Number(useParams().id);
    const navigate = useNavigate();
    const agreement = useApi((signal) => getRentalAgreement(agreementId, signal), [agreementId]);
    const charges = useApi((signal) => listRentalInvoiceCharges(agreementId, signal), [agreementId]);
    const [form, setForm] = useState({ invoice_date: today(), due_date: '', exchange_rate: '1.000000', notes: '' });
    const [quantities, setQuantities] = useState<Record<number, string>>({});
    const [preview, setPreview] = useState<RentalInvoicePreview | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => {
        if (!charges.data) return;
        setQuantities(Object.fromEntries(charges.data
            .filter((charge) => charge.invoice_status !== 'invoiced')
            .map((charge) => [charge.id, charge.remaining_invoice_quantity ?? charge.quantity])));
    }, [charges.data]);
    if (agreement.loading || charges.loading) return <LoadingState />;
    if (!agreement.data) return <ErrorAlert error={agreement.error} />;
    const selected = () => Object.fromEntries(Object.entries(quantities).filter(([, quantity]) => isPositiveDecimal(quantity)));
    const payload = () => ({
        invoice_date: form.invoice_date,
        due_date: form.due_date || undefined,
        exchange_rate: form.exchange_rate,
        notes: form.notes || undefined,
        charge_quantities: selected(),
    });
    const hasSelection = Object.values(quantities).some(isPositiveDecimal);

    return (
        <>
            <ContentHeader title={`${agreement.data.direction === 'outbound' ? 'Customer rental invoice' : 'Supplier rental payable'} / ${agreement.data.agreement_number}`} description={`Creates an ${agreement.data.direction} document through the existing Invoice module.`} />
            <ErrorAlert error={error ?? agreement.error ?? charges.error} />
            <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <DataTable rows={charges.data ?? []} rowKey={(row) => row.id} columns={[
                    { key: 'charge', header: 'Charge', render: (row) => row.description },
                    { key: 'quantity', header: 'Quantity', render: (row) => row.quantity },
                    { key: 'invoiced', header: 'Already invoiced', render: (row) => row.invoiced_quantity ?? '0.000000' },
                    { key: 'remaining', header: 'Remaining', render: (row) => row.remaining_invoice_quantity ?? row.quantity },
                    { key: 'now', header: 'Invoice now', render: (row) => row.invoice_status === 'invoiced' ? 'Complete' : <DecimalInput value={quantities[row.id] ?? ''} max={row.remaining_invoice_quantity ?? row.quantity} onChange={(event) => {
                        setPreview(null);
                        setQuantities((current) => ({ ...current, [row.id]: event.target.value }));
                    }} /> },
                    { key: 'rate', header: 'Rate', render: (row) => row.rate },
                    { key: 'total', header: 'Total', render: (row) => row.total_amount },
                ]} />
                <Panel title="Invoice details">
                    <div className="space-y-4">
                        <Input label="Invoice date" type="date" value={form.invoice_date} error={fieldError(error, 'invoice_date')} onChange={(event) => setForm({ ...form, invoice_date: event.target.value })} />
                        <Input label="Due date" type="date" value={form.due_date} error={fieldError(error, 'due_date')} onChange={(event) => setForm({ ...form, due_date: event.target.value })} />
                        <DecimalInput label="Exchange rate" value={form.exchange_rate} error={fieldError(error, 'exchange_rate')} onChange={(event) => setForm({ ...form, exchange_rate: event.target.value })} />
                        <Textarea label="Notes" value={form.notes} onChange={(event) => setForm({ ...form, notes: event.target.value })} />
                        <div className="flex gap-2">
                            <Button type="button" variant="secondary" loading={busy} disabled={!hasSelection} onClick={async () => {
                                setBusy(true);
                                setError(null);
                                try {
                                    setPreview(await previewRentalInvoice(agreementId, payload()));
                                } catch (requestError) {
                                    setError(toApiError(requestError));
                                } finally {
                                    setBusy(false);
                                }
                            }}>Preview</Button>
                            <Button type="button" loading={busy} disabled={!hasSelection} onClick={async () => {
                                setBusy(true);
                                setError(null);
                                try {
                                    const invoice = await createRentalInvoice(agreementId, payload());
                                    navigate(`/invoices/${invoice.id}`);
                                } catch (requestError) {
                                    setError(toApiError(requestError));
                                } finally {
                                    setBusy(false);
                                }
                            }}>{agreement.data.direction === 'outbound' ? 'Create customer invoice' : 'Create supplier payable'}</Button>
                        </div>
                        {preview && <DetailGrid items={[
                            { label: 'Subtotal', value: preview.subtotal },
                            { label: 'Discount', value: preview.discountTotal },
                            { label: 'Tax', value: preview.taxTotal },
                            { label: 'Charges', value: preview.chargeTotal },
                            { label: 'Grand total', value: preview.grandTotal },
                        ]} />}
                    </div>
                </Panel>
            </div>
        </>
    );
}
