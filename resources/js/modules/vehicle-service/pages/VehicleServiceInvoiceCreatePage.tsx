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
import { isPositiveDecimal } from '@/shared/utils/decimal';
import { createVehicleServiceInvoice, getVehicleServiceJob, listBillableLines, previewVehicleServiceInvoice } from '../vehicleServiceApi';
import type { VehicleServiceInvoicePreview } from '../vehicleServiceTypes';

const today = () => new Date().toISOString().slice(0, 10);

export default function VehicleServiceInvoiceCreatePage() {
    const jobId = Number(useParams().id);
    const navigate = useNavigate();
    const job = useApi((signal) => getVehicleServiceJob(jobId, signal), [jobId]);
    const lines = useApi((signal) => listBillableLines(jobId, signal), [jobId]);
    const [form, setForm] = useState({ invoice_date: today(), due_date: '', exchange_rate: '1.000000', notes: '' });
    const [quantities, setQuantities] = useState<Record<number, string>>({});
    const [preview, setPreview] = useState<VehicleServiceInvoicePreview | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => {
        if (!lines.data) return;
        setQuantities(Object.fromEntries(lines.data
            .filter((line) => line.invoice_state !== 'invoiced')
            .map((line) => [line.id, line.remaining_billable_quantity ?? line.quantity])));
    }, [lines.data]);
    const selectedQuantities = () => Object.fromEntries(
        Object.entries(quantities).filter(([, quantity]) => isPositiveDecimal(quantity)),
    );
    const payload = () => ({
        invoice_date: form.invoice_date,
        due_date: form.due_date || undefined,
        exchange_rate: form.exchange_rate,
        notes: form.notes || undefined,
        line_quantities: selectedQuantities(),
    });
    const hasSelection = Object.values(quantities).some(isPositiveDecimal);
    if (job.loading || lines.loading) return <LoadingState />;
    if (!job.data) return <ErrorAlert error={job.error} />;

    return (
        <>
            <ContentHeader title={`Invoice ${job.data.job_number}`} description="Only billable service-job lines are sent to InvoiceCreationService." />
            <ErrorAlert error={error ?? job.error ?? lines.error} />
            <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <DataTable rows={lines.data ?? []} rowKey={(line) => line.id} columns={[
                    { key: 'line', header: 'Line', render: (line) => line.line_number },
                    { key: 'description', header: 'Description', render: (line) => line.description },
                    { key: 'quantity', header: 'Job quantity', render: (line) => line.quantity },
                    { key: 'invoiced', header: 'Already invoiced', render: (line) => line.invoiced_quantity ?? '0.000000' },
                    { key: 'remaining', header: 'Remaining', render: (line) => line.remaining_billable_quantity ?? line.quantity },
                    { key: 'invoice', header: 'Invoice now', render: (line) => line.invoice_state === 'invoiced'
                        ? <span className="font-semibold text-slate-500">Complete</span>
                        : <DecimalInput value={quantities[line.id] ?? ''} max={line.remaining_billable_quantity ?? line.quantity} onChange={(event) => {
                            setPreview(null);
                            setQuantities((current) => ({ ...current, [line.id]: event.target.value }));
                        }} /> },
                    { key: 'price', header: 'Unit price', render: (line) => line.unit_price },
                    { key: 'total', header: 'Total', render: (line) => line.line_total },
                ]} />
                <Panel title="Invoice details">
                    <div className="space-y-4">
                        <Input label="Invoice date" type="date" value={form.invoice_date} error={fieldError(error, 'invoice_date')} onChange={(event) => setForm({ ...form, invoice_date: event.target.value })} />
                        <Input label="Due date" type="date" value={form.due_date} error={fieldError(error, 'due_date')} onChange={(event) => setForm({ ...form, due_date: event.target.value })} />
                        <DecimalInput label="Exchange rate" value={form.exchange_rate} error={fieldError(error, 'exchange_rate')} onChange={(event) => setForm({ ...form, exchange_rate: event.target.value })} />
                        <Textarea label="Notes" value={form.notes} error={fieldError(error, 'notes')} onChange={(event) => setForm({ ...form, notes: event.target.value })} />
                        <div className="flex gap-2">
                            <Button type="button" variant="secondary" loading={busy} disabled={!hasSelection} onClick={async () => {
                                setBusy(true); setError(null);
                                try { setPreview(await previewVehicleServiceInvoice(jobId, payload())); } catch (requestError) { setError(toApiError(requestError)); } finally { setBusy(false); }
                            }}>Preview</Button>
                            <Button type="button" loading={busy} disabled={!hasSelection} onClick={async () => {
                                setBusy(true); setError(null);
                                try {
                                    const invoice = await createVehicleServiceInvoice(jobId, payload());
                                    const invoiceId = Number(invoice.id);
                                    navigate(Number.isFinite(invoiceId) ? `/invoices/${invoiceId}` : `/vehicle-service/jobs/${jobId}`);
                                } catch (requestError) { setError(toApiError(requestError)); } finally { setBusy(false); }
                            }}>Create invoice</Button>
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
