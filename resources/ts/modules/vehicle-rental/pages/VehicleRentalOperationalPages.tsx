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
    VehicleAvailabilityCalendar,
    VehicleAvailabilityTable,
    VehicleRentalFinancePostingPanel,
    VehicleRentalPageHeader,
} from '../components/VehicleRentalComponents';
import { getAgreementById, getProviderPayableById } from '../mock/vehicleRentalMock';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalBreakdown, VehicleRentalInvoice, VehicleRentalPayment, VehicleRentalProviderPayable, VehicleRentalReplacement } from '../types/vehicleRental.types';

export function VehicleAvailabilityPage() {
    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                subtitle="Vehicle availability is backend-owned. The UI sends date/vehicle/provider context and displays returned availability/conflict results."
                title="Vehicle Availability"
            />
            <FormSection description="No overlap or availability calculations run in the frontend." title="Availability preview input">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Input placeholder="Vehicle / provider / fleet group" />
                    <Input type="datetime-local" />
                    <Input type="datetime-local" />
                    <Button variant="blue">Preview Availability</Button>
                </div>
            </FormSection>
            <VehicleAvailabilityCalendar />
            <VehicleAvailabilityTable />
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
    const agreement = getAgreementById('agr-001');

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/invoices"><Button variant="secondary">All Invoices</Button></Link>}
                subtitle="Readonly rental invoice view. Backend owns billing breakdown, document rendering, payment allocation, and finance posting."
                title={id}
            />
            <RentalBillingPreviewPanel agreement={agreement} />
            <VehicleRentalFinancePostingPanel agreement={agreement} />
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
    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<><Link to="/vehicle-rental/payments"><Button variant="secondary">Cancel</Button></Link><Button variant="blue">Preview Allocation</Button></>}
                subtitle="Collect customer rental payment inputs. Allocation and balance are backend-owned."
                title="New Rental Payment"
            />
            <FormSection description="Payment allocation preview is displayed as a backend/mock result." title="Rental payment">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input placeholder="Agreement / invoice" />
                    <Select options={[{ label: 'Cash', value: 'cash' }, { label: 'Bank', value: 'bank' }, { label: 'Card', value: 'card' }]} />
                    <Input type="date" />
                    <Input placeholder="Amount" />
                    <Input placeholder="Reference" />
                    <Input readOnly value="Backend allocation preview" />
                    <Textarea className="md:col-span-2" placeholder="Payment notes" />
                </div>
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
    const payable = getProviderPayableById(id);

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
