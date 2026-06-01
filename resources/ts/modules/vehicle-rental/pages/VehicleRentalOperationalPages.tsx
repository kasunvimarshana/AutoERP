import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    BreakdownPanel,
    RentalBillingPreviewPanel,
    RentalInvoicePanel,
    RentalPaymentCreateForm,
    RentalPaymentPanel,
    RentalProviderPayablePanel,
    ReplacementVehiclePanel,
    VehicleAvailabilityForm,
    VehicleAvailabilityTable,
    VehicleRentalFinancePostingPanel,
    VehicleRentalPageHeader,
} from '../components/VehicleRentalComponents';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalBreakdown, VehicleRentalInvoice, VehicleRentalPayment, VehicleRentalProviderPayable, VehicleRentalReplacement } from '../types/vehicleRental.types';

function BackendGapPage({ title, description }: { description: string; title: string }) {
    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader subtitle={description} title={title} />
            <EmptyState
                description="The current backend exposes agreements, running charts, availability, invoices, payment allocation, provider payables, replacements, breakdowns, settings and finance workflow. This specific standalone workflow needs a backend contract before a production CRUD page can be truthful."
                title="Backend contract required"
            />
        </div>
    );
}

export function VehicleAvailabilityPage() {
    const [rows, setRows] = useState<Array<{ availability: string; decision: string; id: string; source: string; vehicle: string; window: string }>>([]);

    useEffect(() => {
        vehicleRentalApi.availability.list().then((response) => setRows(response.data)).catch(() => setRows([]));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                subtitle="Vehicle availability is backend-owned. The UI sends date/vehicle/provider context and displays returned availability/conflict results."
                title="Vehicle Availability"
            />
            <VehicleAvailabilityForm />
            <VehicleAvailabilityTable rows={rows} />
        </div>
    );
}

export function RentalInvoiceListPage() {
    const [rows, setRows] = useState<VehicleRentalInvoice[]>([]);
    const [error, setError] = useState<string>();

    useEffect(() => {
        vehicleRentalApi.invoices.list().then((response) => setRows(response.data)).catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load rental invoices.'));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader actions={<Link to="/vehicle-rental/invoices/new"><Button>Generate Invoice</Button></Link>} subtitle="Rental invoices are generated from agreements. Backend owns rental billing, tax, documents, AR, and payment allocation." title="Rental Invoices" />
            {error ? <EmptyState description={error} title="Invoices unavailable" /> : <RentalInvoicePanel rows={rows} />}
        </div>
    );
}

export function RentalInvoiceCreatePage() {
    const [agreements, setAgreements] = useState<Array<{ id: string; label: string }>>([]);
    const [agreementId, setAgreementId] = useState('');
    const [documentTypeId, setDocumentTypeId] = useState('');
    const [message, setMessage] = useState<string>();
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        vehicleRentalApi.agreements.list().then((response) => setAgreements(response.data.map((agreement) => ({ id: agreement.id, label: `${agreement.agreementNumber} - ${agreement.customer}` })))).catch(() => undefined);
    }, []);

    async function generate(): Promise<void> {
        setSaving(true);
        setMessage(undefined);
        try {
            await vehicleRentalApi.invoices.generate(agreementId, documentTypeId);
            setMessage('Invoice generation request completed by backend.');
        } catch (caught) {
            setMessage(caught instanceof Error ? caught.message : 'Unable to generate invoice.');
        } finally {
            setSaving(false);
        }
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader actions={<Link to="/vehicle-rental/invoices"><Button variant="secondary">All Invoices</Button></Link>} subtitle="Generates official rental invoice/document through backend workflow." title="Generate Rental Invoice" />
            <PreviewPanel rows={[
                { label: 'Agreement', value: <select className="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2" onChange={(event) => setAgreementId(event.target.value)} value={agreementId}><option value="">Select agreement</option>{agreements.map((agreement) => <option key={agreement.id} value={agreement.id}>{agreement.label}</option>)}</select> },
                { label: 'Document type ID', value: <input className="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2" onChange={(event) => setDocumentTypeId(event.target.value)} value={documentTypeId} /> },
                { label: 'Backend contract', value: 'POST /api/vehicle-rental/workflow/agreements/{id}/invoice' },
            ]} status="Backend Workflow" title="Invoice Generation" />
            {message ? <EmptyState description={message} title="Invoice generation result" /> : null}
            <div className="flex justify-end"><Button disabled={saving || !agreementId} onClick={generate} variant="blue">{saving ? 'Generating...' : 'Generate Invoice'}</Button></div>
        </div>
    );
}

export function RentalInvoiceDetailPage() {
    const { id = '' } = useParams();
    const [invoice, setInvoice] = useState<VehicleRentalInvoice>();
    const [error, setError] = useState<string>();

    useEffect(() => {
        vehicleRentalApi.invoices.get(id).then((response) => setInvoice(response.data)).catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load rental invoice.'));
    }, [id]);

    if (error) {
        return <EmptyState description={error} title="Rental invoice unavailable" />;
    }

    if (!invoice) {
        return <EmptyState description="Loading rental invoice from backend..." title="Loading invoice" />;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader actions={<Link to="/vehicle-rental/invoices"><Button variant="secondary">All Invoices</Button></Link>} subtitle="Readonly rental invoice view. Backend owns billing breakdown, document rendering, payment allocation, and finance posting." title={invoice.invoiceNumber} />
            <PreviewPanel rows={[
                { label: 'Agreement', value: invoice.sourceAgreement },
                { label: 'Customer', value: invoice.customer },
                { label: 'Billing', value: invoice.billingPreview },
                { label: 'Document status', value: invoice.documentStatus },
                { label: 'Status', value: invoice.status },
            ]} status="Invoice" title="Rental Invoice Summary" />
        </div>
    );
}

export function RentalPaymentListPage() {
    const [rows, setRows] = useState<VehicleRentalPayment[]>([]);

    useEffect(() => {
        vehicleRentalApi.payments.list().then((response) => setRows(response.data)).catch(() => setRows([]));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader actions={<Link to="/vehicle-rental/payments/new"><Button>Allocate Payment</Button></Link>} subtitle="Rental payments are generic Payment integrations. Backend owns allocation, balances, refunds, and finance effects." title="Rental Payments" />
            <RentalPaymentPanel rows={rows} />
        </div>
    );
}

export function RentalPaymentCreatePage() {
    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader actions={<Link to="/vehicle-rental/payments"><Button variant="secondary">Cancel</Button></Link>} subtitle="Allocate an existing core Payment to a rental agreement/document." title="New Rental Payment Allocation" />
            <RentalPaymentCreateForm />
        </div>
    );
}

export function ProviderPayableListPage() {
    const [rows, setRows] = useState<VehicleRentalProviderPayable[]>([]);

    useEffect(() => {
        vehicleRentalApi.providerPayables.list().then((response) => setRows(response.data)).catch(() => setRows([]));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader subtitle="Provider payables are separate from customer invoices and represent external-provider/AP workflow." title="Provider Payables" />
            <RentalProviderPayablePanel rows={rows} />
        </div>
    );
}

export function ProviderPayableDetailPage() {
    const { id = '' } = useParams();
    const [payable, setPayable] = useState<VehicleRentalProviderPayable>();
    const [error, setError] = useState<string>();

    useEffect(() => {
        vehicleRentalApi.providerPayables.get(id).then((response) => setPayable(response.data)).catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load provider payable.'));
    }, [id]);

    if (error) {
        return <EmptyState description={error} title="Provider payable unavailable" />;
    }

    if (!payable) {
        return <EmptyState description="Loading provider payable from backend..." title="Loading payable" />;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader actions={<Link to="/vehicle-rental/provider-payables"><Button variant="secondary">All Payables</Button></Link>} subtitle="Readonly provider payable view. Backend owns AP posting, payment allocation, balances, and status transitions." title={payable.payableNumber} />
            <PreviewPanel rows={[
                { label: 'Agreement', value: payable.agreementNumber },
                { label: 'Provider', value: payable.provider },
                { label: 'Payable amount', value: payable.payablePreview },
                { label: 'Payment status', value: payable.paymentStatus },
                { label: 'Finance status', value: payable.financeStatus },
            ]} status="Provider Payable" title="Provider Payable Summary" />
        </div>
    );
}

export function ReplacementVehicleListPage() {
    const [rows, setRows] = useState<VehicleRentalReplacement[]>([]);

    useEffect(() => {
        vehicleRentalApi.replacements.list().then((response) => setRows(response.data)).catch(() => setRows([]));
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
        vehicleRentalApi.breakdowns.list().then((response) => setRows(response.data)).catch(() => setRows([]));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader subtitle="Breakdowns link vehicles, running charts, replacements, and workflow history." title="Breakdowns" />
            <BreakdownPanel rows={rows} />
        </div>
    );
}

export function VehicleRentalRatesPage() {
    return <BackendGapPage description="Current backend stores rates inside agreements, not as standalone rate-plan master data." title="Rental Rates" />;
}

export function VehicleRentalChargesPage() {
    return <BackendGapPage description="Current backend stores charge lines and extra charges per agreement." title="Rental Charges" />;
}

export function VehicleRentalDepositsPage() {
    return <BackendGapPage description="Current backend stores deposit and advance amounts on agreements and uses Payment allocation workflow." title="Deposits / Advances" />;
}

export function VehicleRentalRefundsPage() {
    return <BackendGapPage description="Current backend has payment allocation and provider-payable workflow, but no standalone rental refund endpoint." title="Rental Refunds" />;
}

export function VehicleRentalCheckoutPage() {
    return <BackendGapPage description="Current backend represents checkout/check-in usage through running charts." title="Checkout" />;
}

export function VehicleRentalCheckinPage() {
    return <BackendGapPage description="Current backend represents check-in/return usage through running charts." title="Check-in / Return" />;
}
