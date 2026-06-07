import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { getVehicleServiceJob, prepareVehicleServicePayment } from '../vehicleServiceApi';

const today = () => new Date().toISOString().slice(0, 10);

export default function VehicleServicePaymentPreparePage() {
    const jobId = Number(useParams().id);
    const job = useApi((signal) => getVehicleServiceJob(jobId, signal), [jobId]);
    const [invoiceId, setInvoiceId] = useState('');
    const [date, setDate] = useState(today());
    const [amount, setAmount] = useState('0.000000');
    const [reference, setReference] = useState('');
    const [prepared, setPrepared] = useState<Record<string, unknown> | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    if (job.loading) return <LoadingState />;
    if (!job.data) return <ErrorAlert error={job.error} />;

    return (
        <>
            <ContentHeader title={`Prepare payment for ${job.data.job_number}`} description="This prepares Payment module data only; Payment owns creation and invoice allocation." />
            <ErrorAlert error={error} />
            <Panel>
                <form className="grid gap-4 md:grid-cols-2" onSubmit={async (event) => {
                    event.preventDefault();
                    setBusy(true); setError(null);
                    try {
                        setPrepared(await prepareVehicleServicePayment(jobId, {
                            invoice_id: Number(invoiceId),
                            payment_date: date,
                            amount,
                            reference_number: reference || undefined,
                        }));
                    } catch (requestError) {
                        setError(toApiError(requestError));
                    } finally {
                        setBusy(false);
                    }
                }}>
                    <Select label="Invoice" value={invoiceId} options={(job.data.invoice_links ?? []).map((link) => ({ value: link.invoice_id, label: `${link.invoice_number ?? `Invoice #${link.invoice_id}`} / balance ${link.balance_due ?? link.invoice_total}` }))} onChange={(event) => {
                        setInvoiceId(event.target.value);
                        const link = job.data?.invoice_links?.find((entry) => entry.invoice_id === Number(event.target.value));
                        if (link?.balance_due) setAmount(link.balance_due);
                    }} />
                    <Input label="Payment date" type="date" value={date} onChange={(event) => setDate(event.target.value)} />
                    <Input label="Amount" type="number" min="0.000001" step="0.000001" value={amount} onChange={(event) => setAmount(event.target.value)} />
                    <Input label="Reference" value={reference} onChange={(event) => setReference(event.target.value)} />
                    <Button type="submit" loading={busy}>Prepare Payment DTO</Button>
                </form>
                {prepared && <pre className="mt-5 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{JSON.stringify(prepared, null, 2)}</pre>}
            </Panel>
        </>
    );
}
