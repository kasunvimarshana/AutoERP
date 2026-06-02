import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import {
    BreakdownPanel,
    RentalBillingPreviewPanel,
    RentalInvoicePanel,
    RentalPaymentPanel,
    RentalProviderPayablePanel,
    ReplacementVehiclePanel,
    VehicleRentalFinancePostingPanel,
    VehicleRentalPageHeader,
} from '../components/VehicleRentalComponents';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalAgreement, VehicleRentalBreakdown, VehicleRentalInvoice, VehicleRentalPayment, VehicleRentalProviderPayable, VehicleRentalReplacement } from '../types/vehicleRental.types';

export function VehicleAvailabilityPage() {
    const [vehicleId, setVehicleId] = useState('');
    const [start, setStart] = useState('');
    const [end, setEnd] = useState('');
    const [result, setResult] = useState<string>();

    async function preview() {
        const response = await vehicleRentalApi.availability.preview({ end_datetime: end, rental_vehicle_id: vehicleId, start_datetime: start });
        setResult(response.calculated.availabilityDecision);
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                subtitle="Vehicle availability is backend-owned. The UI sends date/vehicle/provider context and displays returned availability/conflict results."
                title="Vehicle Availability"
            />
            <FormSection description="No overlap or availability calculations run in the frontend." title="Availability preview input">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Input onChange={(event) => setVehicleId(event.target.value)} placeholder="Rental vehicle id" value={vehicleId} />
                    <Input onChange={(event) => setStart(event.target.value)} type="datetime-local" value={start} />
                    <Input onChange={(event) => setEnd(event.target.value)} type="datetime-local" value={end} />
                    <Button onClick={() => void preview()} type="button" variant="blue">Preview Availability</Button>
                </div>
            </FormSection>
            {result ? <PreviewPanel rows={[{ label: 'Backend decision', value: result }]} status="Availability" title="Availability Result" /> : null}
        </div>
    );
}

export function RentalInvoiceListPage() {
    const [rows, setRows] = useState<VehicleRentalInvoice[]>([]);

    useEffect(() => {
        vehicleRentalApi.invoices.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader subtitle="Rental invoices are generated from agreements/running charts. Backend owns rental billing, tax, documents, AR, and payment allocation." title="Rental Invoices" />
            <RentalInvoicePanel rows={rows} />
        </div>
    );
}

export function RentalInvoiceDetailPage() {
    const { id = 'inv-001' } = useParams();
    const [agreement, setAgreement] = useState<VehicleRentalAgreement>();

    useEffect(() => {
        vehicleRentalApi.agreements.get(id).then((response) => setAgreement(response.data)).catch(() => undefined);
    }, [id]);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/invoices"><Button variant="secondary">All Invoices</Button></Link>}
                subtitle="Readonly rental invoice view. Backend owns billing breakdown, document rendering, payment allocation, and finance posting."
                title={id}
            />
            {agreement ? <RentalBillingPreviewPanel agreement={agreement} /> : null}
            {agreement ? <VehicleRentalFinancePostingPanel agreement={agreement} /> : null}
        </div>
    );
}

export function RentalPaymentListPage() {
    const [rows, setRows] = useState<VehicleRentalPayment[]>([]);

    useEffect(() => {
        vehicleRentalApi.payments.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/payments/new"><Button>Record Payment</Button></Link>}
                subtitle="Rental payments are generic Payment integrations. Backend owns allocation, customer balance, refunds, and finance effects."
                title="Rental Payments"
            />
            <RentalPaymentPanel rows={rows} />
        </div>
    );
}

export function RentalPaymentCreatePage() {
    const [agreementId, setAgreementId] = useState('');
    const [paymentId, setPaymentId] = useState('');
    const [amount, setAmount] = useState('');
    const [message, setMessage] = useState<string>();

    async function allocate() {
        await vehicleRentalApi.payments.allocateLessee(agreementId, { amount: Number(amount || 0), payment_id: Number(paymentId || 0) });
        setMessage('Payment allocation submitted to backend.');
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/payments"><Button variant="secondary">Cancel</Button></Link>}
                subtitle="Collect customer rental payment inputs. Allocation and balance are backend-owned."
                title="New Rental Payment"
            />
            <FormSection description="Payment allocation is sent to backend. Frontend does not calculate balances." title="Rental payment">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input onChange={(event) => setAgreementId(event.target.value)} placeholder="Lessee agreement id" value={agreementId} />
                    <Select options={[{ label: 'Cash', value: 'cash' }, { label: 'Bank', value: 'bank' }, { label: 'Card', value: 'card' }]} />
                    <Input type="date" />
                    <Input onChange={(event) => setAmount(event.target.value)} placeholder="Amount" value={amount} />
                    <Input onChange={(event) => setPaymentId(event.target.value)} placeholder="Payment id" value={paymentId} />
                    <Textarea className="md:col-span-2" placeholder="Payment notes" />
                </div>
                <div className="mt-4 flex justify-end"><Button onClick={() => void allocate()} type="button" variant="blue">Allocate Payment</Button></div>
                {message ? <p className="mt-3 text-sm font-semibold text-slate-600">{message}</p> : null}
            </FormSection>
        </div>
    );
}

export function ProviderPayableListPage() {
    const [rows, setRows] = useState<VehicleRentalProviderPayable[]>([]);

    useEffect(() => {
        vehicleRentalApi.providerPayables.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader subtitle="Provider payables are separate from customer invoices and represent external-provider/AP workflow." title="Provider Payables" />
            <RentalProviderPayablePanel rows={rows} />
        </div>
    );
}

export function ProviderPayableDetailPage() {
    const { id = 'payable-001' } = useParams();
    const [payable, setPayable] = useState<VehicleRentalProviderPayable>();

    useEffect(() => {
        vehicleRentalApi.providerPayables.get(id).then((response) => setPayable(response.data));
    }, [id]);

    if (!payable) {
        return <div className="space-y-6"><VehicleRentalPageHeader title="Loading payable" /></div>;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/provider-payables"><Button variant="secondary">All Payables</Button></Link>}
                subtitle="Readonly provider payable view. Backend owns AP posting, payment allocation, balances, and status transitions."
                title={payable.payableNumber}
            />
            <PreviewPanel
                rows={[
                    { label: 'Agreement', value: payable.agreementNumber },
                    { label: 'Provider', value: payable.provider },
                    { label: 'Payable amount', value: payable.payablePreview },
                    { label: 'Payment status', value: payable.paymentStatus },
                    { label: 'Finance status', value: payable.financeStatus },
                ]}
                status="Provider Payable"
                title="Provider Payable Summary"
            />
        </div>
    );
}

export function ReplacementVehicleListPage() {
    const [rows, setRows] = useState<VehicleRentalReplacement[]>([]);

    useEffect(() => {
        vehicleRentalApi.replacements.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader subtitle="Replacement vehicles are rental workflow records. Backend validates replacement availability and provider rules." title="Replacement Vehicles" />
            <ReplacementVehiclePanel rows={rows} />
        </div>
    );
}

export function BreakdownListPage() {
    const [rows, setRows] = useState<VehicleRentalBreakdown[]>([]);

    useEffect(() => {
        vehicleRentalApi.breakdowns.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader subtitle="Breakdowns link vehicles, running charts, replacements, and workflow history." title="Breakdowns" />
            <BreakdownPanel rows={rows} />
        </div>
    );
}
