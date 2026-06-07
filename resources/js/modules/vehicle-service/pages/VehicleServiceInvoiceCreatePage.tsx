import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { createVehicleServiceInvoice, getVehicleServiceJob, listBillableLines, previewVehicleServiceInvoice } from '../vehicleServiceApi';

const today = () => new Date().toISOString().slice(0, 10);

export default function VehicleServiceInvoiceCreatePage() {
    const jobId = Number(useParams().id);
    const navigate = useNavigate();
    const job = useApi((signal) => getVehicleServiceJob(jobId, signal), [jobId]);
    const lines = useApi((signal) => listBillableLines(jobId, signal), [jobId]);
    const [form, setForm] = useState({ invoice_date: today(), due_date: '', exchange_rate: '1.000000', notes: '' });
    const [preview, setPreview] = useState<Record<string, unknown> | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const payload = () => ({ invoice_date: form.invoice_date, due_date: form.due_date || undefined, exchange_rate: form.exchange_rate, notes: form.notes || undefined });
    if (job.loading || lines.loading) return <LoadingState />;
    if (!job.data) return <ErrorAlert error={job.error} />;

    return (
        <>
            <ContentHeader title={`Invoice ${job.data.job_number}`} description="Only billable service-job lines are sent to InvoiceCreationService." />
            <ErrorAlert error={error ?? lines.error} />
            <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <DataTable rows={lines.data ?? []} rowKey={(line) => line.id} columns={[
                    { key: 'line', header: 'Line', render: (line) => line.line_number },
                    { key: 'description', header: 'Description', render: (line) => line.description },
                    { key: 'quantity', header: 'Quantity', render: (line) => line.quantity },
                    { key: 'price', header: 'Unit price', render: (line) => line.unit_price },
                    { key: 'total', header: 'Total', render: (line) => line.line_total },
                ]} />
                <Panel title="Invoice details">
                    <div className="space-y-4">
                        <Input label="Invoice date" type="date" value={form.invoice_date} onChange={(event) => setForm({ ...form, invoice_date: event.target.value })} />
                        <Input label="Due date" type="date" value={form.due_date} onChange={(event) => setForm({ ...form, due_date: event.target.value })} />
                        <Input label="Exchange rate" type="number" min="0.000001" step="0.000001" value={form.exchange_rate} onChange={(event) => setForm({ ...form, exchange_rate: event.target.value })} />
                        <Textarea label="Notes" value={form.notes} onChange={(event) => setForm({ ...form, notes: event.target.value })} />
                        <div className="flex gap-2">
                            <Button type="button" variant="secondary" loading={busy} onClick={async () => {
                                setBusy(true); setError(null);
                                try { setPreview(await previewVehicleServiceInvoice(jobId, payload())); } catch (requestError) { setError(toApiError(requestError)); } finally { setBusy(false); }
                            }}>Preview</Button>
                            <Button type="button" loading={busy} onClick={async () => {
                                setBusy(true); setError(null);
                                try {
                                    const invoice = await createVehicleServiceInvoice(jobId, payload());
                                    const invoiceId = Number(invoice.id);
                                    navigate(Number.isFinite(invoiceId) ? `/invoices/${invoiceId}` : `/vehicle-service/jobs/${jobId}`);
                                } catch (requestError) { setError(toApiError(requestError)); } finally { setBusy(false); }
                            }}>Create invoice</Button>
                        </div>
                        {preview && <pre className="overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{JSON.stringify(preview, null, 2)}</pre>}
                    </div>
                </Panel>
            </div>
        </>
    );
}
