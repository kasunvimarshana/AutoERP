import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
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
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import { allocateRentalVehicle, changeRentalAgreementStatus, createRentalPayment, getRentalAgreement, prepareRentalPayment, replaceRentalVehicle } from '../vehicleRentalApi';

const today = () => new Date().toISOString().slice(0, 10);

export default function RentalAgreementDetailPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const result = useApi((signal) => getRentalAgreement(id, signal), [id]);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [replaceAllocationId, setReplaceAllocationId] = useState<number | null>(null);
    const [allocation, setAllocation] = useState({ allocated_from: '', allocated_to: '', start_odometer: '', remarks: '' });
    const [payment, setPayment] = useState({ link_type: 'deposit', invoice_id: '', payment_date: today(), amount: '', exchange_rate: '1.000000', reference_number: '' });
    const [paymentPreview, setPaymentPreview] = useState<Record<string, unknown> | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const agreement = result.data;
    const doAction = async (status: 'confirm' | 'activate' | 'returned' | 'complete' | 'cancel') => {
        if (busy) return;
        setBusy(true);
        setError(null);
        try {
            await changeRentalAgreementStatus(agreement.id, status);
            result.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };
    const invoiceOptions = Array.from(new Map(agreement.invoice_links.filter((link) => link.status === 'active').map((link) => [link.invoice_id, link])).values());

    return (
        <>
            <ContentHeader title={agreement.agreement_number} description={`${agreement.direction === 'outbound' ? 'Outbound rental' : 'Inbound hire-in'} / ${readableRelation(agreement.party)}`} actions={<>
                {agreement.status === 'draft' && <Button type="button" loading={busy} onClick={() => doAction('confirm')}>Confirm</Button>}
                {agreement.status === 'confirmed' && <Button type="button" loading={busy} onClick={() => doAction('activate')}>Activate</Button>}
                {agreement.status === 'active' && <Button type="button" loading={busy} onClick={() => doAction('returned')}>Mark returned</Button>}
                {agreement.status === 'returned' && <Button type="button" loading={busy} onClick={() => doAction('complete')}>Complete</Button>}
                {!['completed', 'cancelled'].includes(agreement.status) && <Button type="button" variant="danger" loading={busy} onClick={() => doAction('cancel')}>Cancel</Button>}
            </>} />
            <ErrorAlert error={error ?? result.error} />
            <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div className="space-y-5">
                    <Panel title="Agreement">
                        <DetailGrid items={[
                            { label: 'Status', value: <RentalStatusBadge status={agreement.status} /> },
                            { label: 'Party', value: readableRelation(agreement.party) },
                            { label: 'Rental type', value: agreement.rental_type.replaceAll('_', ' ') },
                            { label: 'Billing cycle', value: agreement.billing_cycle.replaceAll('_', ' ') },
                            { label: 'Billing basis', value: agreement.billing_basis.replaceAll('_', ' ') },
                            { label: 'Agreement date', value: formatDate(agreement.agreement_date) },
                            { label: 'Period', value: `${formatDate(agreement.start_at)} to ${formatDate(agreement.expected_end_at)}` },
                        ]} />
                        {agreement.remarks && <p className="mt-4 text-sm text-slate-600">{agreement.remarks}</p>}
                    </Panel>
                    <Panel title="Vehicle allocation">
                        <DataTable rows={agreement.vehicles} rowKey={(row) => row.id} columns={[
                            { key: 'vehicle', header: 'Vehicle', render: (row) => row.vehicle?.registration_number ?? readableRelation(row.vehicle) },
                            { key: 'period', header: 'Period', render: (row) => `${formatDate(row.allocated_from)} to ${formatDate(row.allocated_to ?? agreement.expected_end_at)}` },
                            { key: 'odometer', header: 'Odometer', render: (row) => `${row.start_odometer} / ${row.end_odometer ?? '-'}` },
                            { key: 'status', header: 'Status', render: (row) => <RentalStatusBadge status={row.status} /> },
                            { key: 'actions', header: '', render: (row) => <div className="flex gap-2">
                                {!row.pickup_inspection && <Link to={`/vehicle-rental/agreements/${agreement.id}/vehicles/${row.id}/pickup`}><Button type="button" variant="secondary">Pickup</Button></Link>}
                                {row.pickup_inspection && !row.return_inspection && <Link to={`/vehicle-rental/agreements/${agreement.id}/vehicles/${row.id}/return`}><Button type="button" variant="secondary">Return</Button></Link>}
                                {agreement.status === 'active' && row.status === 'active' && <Button type="button" variant="secondary" onClick={() => {
                                    setReplaceAllocationId(row.id);
                                    setVehicle(null);
                                    setAllocation({ allocated_from: '', allocated_to: '', start_odometer: '', remarks: '' });
                                }}>Replace</Button>}
                            </div> },
                        ]} />
                        {(['draft', 'confirmed'].includes(agreement.status) || replaceAllocationId !== null) && <form className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3" onSubmit={async (event) => {
                            event.preventDefault();
                            setBusy(true);
                            setError(null);
                            try {
                                const payload = {
                                    vehicle_id: vehicle?.id,
                                    allocated_from: allocation.allocated_from || agreement.start_at,
                                    allocated_to: allocation.allocated_to || agreement.expected_end_at,
                                    start_odometer: allocation.start_odometer || vehicle?.odometer_reading,
                                    owner_party_type: agreement.direction === 'inbound' ? agreement.party_type : undefined,
                                    owner_party_id: agreement.direction === 'inbound' ? agreement.party_id : undefined,
                                    remarks: allocation.remarks || undefined,
                                };
                                if (replaceAllocationId !== null) {
                                    await replaceRentalVehicle(agreement.id, replaceAllocationId, payload);
                                } else {
                                    await allocateRentalVehicle(agreement.id, payload);
                                }
                                setVehicle(null);
                                setReplaceAllocationId(null);
                                setAllocation({ allocated_from: '', allocated_to: '', start_odometer: '', remarks: '' });
                                result.reload();
                            } catch (requestError) {
                                setError(toApiError(requestError));
                            } finally {
                                setBusy(false);
                            }
                        }}>
                            <VehicleLookupSelect value={vehicle} onChange={(value) => {
                                setVehicle(value);
                                setAllocation((current) => ({ ...current, start_odometer: value?.odometer_reading ?? '' }));
                            }} error={fieldError(error, 'vehicle_id')} />
                            <Input label="Allocated from" type="datetime-local" value={allocation.allocated_from} placeholder={agreement.start_at.slice(0, 16)} onChange={(event) => setAllocation({ ...allocation, allocated_from: event.target.value })} />
                            <Input label="Allocated to" type="datetime-local" value={allocation.allocated_to} placeholder={agreement.expected_end_at.slice(0, 16)} onChange={(event) => setAllocation({ ...allocation, allocated_to: event.target.value })} />
                            <DecimalInput label="Start odometer" value={allocation.start_odometer} error={fieldError(error, 'start_odometer')} onChange={(event) => setAllocation({ ...allocation, start_odometer: event.target.value })} />
                            <Textarea label="Remarks" value={allocation.remarks} onChange={(event) => setAllocation({ ...allocation, remarks: event.target.value })} />
                            <div className="flex items-end gap-2">
                                {replaceAllocationId !== null && <Button type="button" variant="secondary" onClick={() => setReplaceAllocationId(null)}>Cancel</Button>}
                                <Button type="submit" loading={busy}>{replaceAllocationId === null ? 'Allocate vehicle' : 'Replace vehicle'}</Button>
                            </div>
                        </form>}
                    </Panel>
                    <Panel title="Financial links">
                        <div className="grid gap-5 lg:grid-cols-2">
                            <div>
                                <h3 className="mb-2 text-sm font-semibold">Invoices</h3>
                                <div className="space-y-2">{invoiceOptions.length === 0 ? <p className="text-sm text-slate-500">No invoice links.</p> : invoiceOptions.map((link) => <Link key={link.invoice_id} className="block text-sm font-semibold text-sky-700 hover:underline" to={`/invoices/${link.invoice_id}`}>{link.invoice_number} / balance {link.balance_due ?? '-'}</Link>)}</div>
                            </div>
                            <div>
                                <h3 className="mb-2 text-sm font-semibold">Payments</h3>
                                <div className="space-y-2">{agreement.payment_links.length === 0 ? <p className="text-sm text-slate-500">No payment links.</p> : agreement.payment_links.map((link) => <Link key={link.id} className="block text-sm font-semibold text-sky-700 hover:underline" to={`/payments/${link.payment_id}`}>{link.payment_number} / {link.link_type} / {link.amount}</Link>)}</div>
                            </div>
                        </div>
                    </Panel>
                </div>
                <div className="space-y-5">
                    <Panel title="Rate snapshot">
                        <DetailGrid items={[
                            { label: 'Base rate', value: agreement.rate_snapshot ? `${agreement.rate_snapshot.base_rate} / ${agreement.rate_snapshot.rate_unit}` : '-' },
                            { label: 'Allowed KM', value: agreement.rate_snapshot?.allowed_km },
                            { label: 'Allowed hours', value: agreement.rate_snapshot?.allowed_hours },
                            { label: 'Extra KM', value: agreement.rate_snapshot?.extra_km_rate },
                            { label: 'Extra hour', value: agreement.rate_snapshot?.extra_hour_rate },
                            { label: 'Driver rate', value: agreement.rate_snapshot?.driver_rate },
                        ]} />
                    </Panel>
                    <Panel title="Workspaces">
                        <div className="grid gap-2">
                            <Link to={`/vehicle-rental/agreements/${agreement.id}/usage`}><Button type="button" variant="secondary" className="w-full">Open in common running chart</Button></Link>
                            <Link to={`/vehicle-rental/agreements/${agreement.id}/expenses`}><Button type="button" variant="secondary" className="w-full">Expenses</Button></Link>
                            <Link to={`/vehicle-rental/agreements/${agreement.id}/charges`}><Button type="button" variant="secondary" className="w-full">Charge preview</Button></Link>
                            <Link to={`/vehicle-rental/agreements/${agreement.id}/invoice`}><Button type="button" variant="secondary" className="w-full">Create invoice / payable</Button></Link>
                        </div>
                    </Panel>
                    <Panel title="Prepare payment">
                        <form className="space-y-4" onSubmit={async (event) => {
                            event.preventDefault();
                            setBusy(true);
                            setError(null);
                            try {
                                const payload = {
                                    ...payment,
                                    invoice_id: payment.invoice_id ? Number(payment.invoice_id) : undefined,
                                    reference_number: payment.reference_number || undefined,
                                };
                                const saved = await createRentalPayment(agreement.id, payload);
                                navigate(`/payments/${saved.id}`);
                            } catch (requestError) {
                                setError(toApiError(requestError));
                            } finally {
                                setBusy(false);
                            }
                        }}>
                            <Select label="Payment purpose" value={payment.link_type} options={['deposit', 'advance', 'settlement', 'refund'].map((value) => ({ value, label: value }))} onChange={(event) => setPayment({ ...payment, link_type: event.target.value })} />
                            {payment.link_type === 'settlement' && <Select label="Rental invoice" value={payment.invoice_id} error={fieldError(error, 'invoice_id')} options={invoiceOptions.map((link) => ({ value: link.invoice_id, label: `${link.invoice_number} / ${link.balance_due ?? '-'}` }))} onChange={(event) => setPayment({ ...payment, invoice_id: event.target.value })} />}
                            <Input label="Payment date" type="date" value={payment.payment_date} onChange={(event) => setPayment({ ...payment, payment_date: event.target.value })} />
                            <DecimalInput label="Amount" value={payment.amount} error={fieldError(error, 'amount')} onChange={(event) => setPayment({ ...payment, amount: event.target.value })} />
                            <Input label="Reference" value={payment.reference_number} onChange={(event) => setPayment({ ...payment, reference_number: event.target.value })} />
                            <div className="flex gap-2">
                                <Button type="button" variant="secondary" loading={busy} onClick={async () => {
                                    setBusy(true);
                                    setError(null);
                                    try {
                                        setPaymentPreview(await prepareRentalPayment(agreement.id, {
                                            ...payment,
                                            invoice_id: payment.invoice_id ? Number(payment.invoice_id) : undefined,
                                        }));
                                    } catch (requestError) {
                                        setError(toApiError(requestError));
                                    } finally {
                                        setBusy(false);
                                    }
                                }}>Preview</Button>
                                <Button type="submit" loading={busy}>Create</Button>
                            </div>
                            {paymentPreview && <p className="rounded-lg bg-sky-50 p-3 text-sm text-sky-800">Payment validated for {String(paymentPreview.paymentType ?? paymentPreview.payment_type ?? payment.link_type)}.</p>}
                        </form>
                    </Panel>
                </div>
            </div>
        </>
    );
}
