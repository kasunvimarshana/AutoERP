import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { compareDecimalStrings } from '@/shared/utils/decimal';
import { createVehicleServicePayment, getVehicleServiceJob, prepareVehicleServicePayment } from '../vehicleServiceApi';
import type { PreparedVehicleServicePayment, VehicleServicePaymentPayload } from '../vehicleServiceTypes';

const today = () => new Date().toISOString().slice(0, 10);

export default function VehicleServicePaymentPreparePage() {
    const jobId = Number(useParams().id);
    const navigate = useNavigate();
    const job = useApi((signal) => getVehicleServiceJob(jobId, signal), [jobId]);
    const [invoiceId, setInvoiceId] = useState('');
    const [date, setDate] = useState(today());
    const [amount, setAmount] = useState('0.000000');
    const [reference, setReference] = useState('');
    const [prepared, setPrepared] = useState<PreparedVehicleServicePayment | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    if (job.loading) return <LoadingState />;
    if (!job.data) return <ErrorAlert error={job.error} />;
    const invoice = job.data.invoice_links?.find((link) => link.invoice_id === Number(invoiceId));
    const payload = (): VehicleServicePaymentPayload => ({
        invoice_id: Number(invoiceId),
        payment_date: date,
        amount,
        reference_number: reference || undefined,
    });

    return (
        <>
            <ContentHeader title={`Payment for ${job.data.job_number}`} description="Review the invoice balance, then create and allocate the receipt through Payment." />
            <ErrorAlert error={error} />
            <Panel>
                <form className="grid gap-4 md:grid-cols-2" onSubmit={async (event) => {
                    event.preventDefault();
                    setBusy(true); setError(null);
                    try {
                        setPrepared(await prepareVehicleServicePayment(jobId, payload()));
                    } catch (requestError) {
                        setError(toApiError(requestError));
                    } finally {
                        setBusy(false);
                    }
                }}>
                    <Select label="Invoice" value={invoiceId} error={fieldError(error, 'invoice_id')} options={(job.data.invoice_links ?? []).filter((link) => link.status === 'active' && compareDecimalStrings(link.balance_due ?? '0', '0') > 0).map((link) => ({ value: link.invoice_id, label: `${link.invoice_number ?? 'Invoice'} / balance ${link.balance_due ?? link.invoice_total}` }))} onChange={(event) => {
                        setInvoiceId(event.target.value);
                        setPrepared(null);
                        const link = job.data?.invoice_links?.find((entry) => entry.invoice_id === Number(event.target.value));
                        if (link?.balance_due) setAmount(link.balance_due);
                    }} />
                    <Input label="Payment date" type="date" value={date} error={fieldError(error, 'payment_date')} onChange={(event) => setDate(event.target.value)} />
                    <DecimalInput label="Amount" value={amount} error={fieldError(error, 'amount')} onChange={(event) => setAmount(event.target.value)} />
                    <Input label="Reference" value={reference} error={fieldError(error, 'reference_number')} onChange={(event) => setReference(event.target.value)} />
                    {invoice && (
                        <div className="rounded-lg border border-sky-100 bg-sky-50 p-4 md:col-span-2">
                            <DetailGrid items={[
                                { label: 'Invoice', value: invoice.invoice_number ?? 'Invoice' },
                                { label: 'Invoice total', value: invoice.invoice_total },
                                { label: 'Outstanding balance', value: invoice.balance_due ?? '-' },
                                { label: 'Invoice status', value: invoice.invoice_status ?? '-' },
                                { label: 'Settlement amount', value: amount },
                            ]} />
                        </div>
                    )}
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit" variant="secondary" loading={busy} disabled={!invoiceId}>Review allocation</Button>
                        <Button type="button" loading={busy} disabled={!invoiceId} onClick={async () => {
                            setBusy(true);
                            setError(null);
                            try {
                                const payment = await createVehicleServicePayment(jobId, payload());
                                navigate(`/payments/${payment.id}`);
                            } catch (requestError) {
                                setError(toApiError(requestError));
                            } finally {
                                setBusy(false);
                            }
                        }}>Create and allocate payment</Button>
                    </div>
                </form>
                {prepared && <div className="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <p className="mb-4 text-sm font-semibold text-emerald-900">Payment data prepared successfully</p>
                    <DetailGrid items={[
                        { label: 'Type', value: prepared.paymentType },
                        { label: 'Direction', value: prepared.direction },
                        { label: 'Payment date', value: prepared.paymentDate },
                        { label: 'Reference', value: prepared.referenceNumber ?? (reference || '-') },
                        { label: 'Amount', value: prepared.lines[0]?.amount ?? amount },
                        { label: 'Invoice', value: job.data.invoice_links?.find((link) => link.invoice_id === Number(invoiceId))?.invoice_number ?? '-' },
                    ]} />
                </div>}
            </Panel>
        </>
    );
}
