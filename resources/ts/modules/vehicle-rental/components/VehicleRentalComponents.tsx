import { useEffect, useMemo, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type {
    VehicleRentalAgreement,
    VehicleRentalAgreementFormInput,
    VehicleRentalAuditEntry,
    VehicleRentalBreakdown,
    VehicleRentalDashboardMetric,
    VehicleRentalInvoice,
    VehicleRentalLookupOption,
    VehicleRentalPayment,
    VehicleRentalProviderPayable,
    VehicleRentalReplacement,
    VehicleRentalRunningChart,
    VehicleRentalRunningChartFormInput,
    VehicleRentalSettings,
} from '../types/vehicleRental.types';

function toSelectOptions(items: VehicleRentalLookupOption[]) {
    return items.map((item) => ({ label: item.secondary ? `${item.label} (${item.secondary})` : item.label, value: item.id }));
}

function today() {
    return new Date().toISOString().slice(0, 10);
}

function Field({ children, error, label }: { children: ReactNode; error?: string; label: string }) {
    return (
        <label className="space-y-2">
            <span className="block text-xs font-bold uppercase tracking-wide text-slate-500">{label}</span>
            {children}
            {error ? <span className="block text-xs font-semibold text-red-600">{error}</span> : null}
        </label>
    );
}

function useVehicleRentalLookups() {
    const [customers, setCustomers] = useState<VehicleRentalLookupOption[]>([]);
    const [providers, setProviders] = useState<VehicleRentalLookupOption[]>([]);
    const [vehicles, setVehicles] = useState<VehicleRentalLookupOption[]>([]);
    const [drivers, setDrivers] = useState<VehicleRentalLookupOption[]>([]);
    const [lesseeAgreements, setLesseeAgreements] = useState<VehicleRentalLookupOption[]>([]);
    const [lessorAgreements, setLessorAgreements] = useState<VehicleRentalLookupOption[]>([]);
    const loadedRef = useRef(new Set<string>());

    async function load(kind: 'customers' | 'providers' | 'vehicles' | 'drivers' | 'lesseeAgreements' | 'lessorAgreements', search = '') {
        const key = `${kind}:${search}`;
        if (loadedRef.current.has(key)) return;
        loadedRef.current.add(key);
        const response = await {
            customers: vehicleRentalApi.lookups.customers,
            drivers: vehicleRentalApi.lookups.drivers,
            lesseeAgreements: vehicleRentalApi.lookups.lesseeAgreements,
            lessorAgreements: vehicleRentalApi.lookups.lessorAgreements,
            providers: vehicleRentalApi.lookups.providers,
            vehicles: vehicleRentalApi.lookups.rentalVehicles,
        }[kind](search);
        const setters = {
            customers: setCustomers,
            drivers: setDrivers,
            lesseeAgreements: setLesseeAgreements,
            lessorAgreements: setLessorAgreements,
            providers: setProviders,
            vehicles: setVehicles,
        };
        setters[kind](response.data);
    }

    return { customers, drivers, lesseeAgreements, lessorAgreements, load, providers, vehicles };
}

const emptyAgreementForm: VehicleRentalAgreementFormInput = {
    agreementDate: today(),
    allowedDailyHours: '8',
    allowedDailyKm: '100',
    billingFrequency: 'per_trip',
    customerId: '',
    depositAmount: '0',
    driverMode: 'without_driver',
    endAt: '',
    lesseeAgreementNumber: '',
    lesseeBaseRate: '0',
    lesseeTerms: '',
    lessorAgreementNumber: '',
    lessorBaseRate: '0',
    lessorPartyId: '',
    lessorPartyName: '',
    lessorPartyType: 'supplier',
    lessorTerms: '',
    pickupLocation: '',
    providerId: '',
    rateModel: 'day',
    rentalVehicleId: '',
    returnLocation: '',
    startAt: '',
    status: 'draft',
    terms: '',
};

export function VehicleRentalPageHeader({ actions, subtitle, title }: { actions?: ReactNode; subtitle?: string; title: string }) {
    return <PageHeader actions={actions} eyebrow="Vehicle Rental" subtitle={subtitle} title={title} />;
}

export function VehicleRentalDashboardCards({ metrics }: { metrics: VehicleRentalDashboardMetric[] }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {metrics.map((metric) => (
                <Card className="p-5" key={`rental-metric-${metric.label}`}>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{metric.label}</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{metric.value}</p>
                    <p className="mt-2 text-xs font-semibold text-slate-500">{metric.tone}</p>
                </Card>
            ))}
        </div>
    );
}

export function RentalAgreementTable({ rows }: { rows: VehicleRentalAgreement[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Agreement', key: 'agreementNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-rental/agreements/${row.id}`}>{row.agreementNumber}</Link> },
                { header: 'Side', key: 'agreementRole', render: (row) => <StatusBadge status={row.agreementRole} /> },
                { header: 'Lessee', key: 'customer' },
                { header: 'Lessor / Provider', key: 'provider' },
                { header: 'Vehicle', key: 'vehicle' },
                { header: 'Period', key: 'period', render: (row) => `${row.startAt || 'Not set'} -> ${row.endAt || 'Open'}` },
                { header: 'Receivable', key: 'receivable', render: (row) => row.billingPreview.calculated.grandTotal },
                { header: 'Payable', key: 'payable', render: (row) => row.billingPreview.calculated.providerPayable },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => `rental-agreement-${row.agreementRole}-${row.id}`}
            rows={rows}
        />
    );
}

export function RentalAgreementForm({ agreement }: { agreement?: VehicleRentalAgreement }) {
    const navigate = useNavigate();
    const lookups = useVehicleRentalLookups();
    const [activeTab, setActiveTab] = useState('header');
    const [form, setForm] = useState<VehicleRentalAgreementFormInput>(() => ({
        ...emptyAgreementForm,
        customerId: agreement?.agreementRole === 'lessee' ? agreement.id : '',
        endAt: agreement?.endAt ?? '',
        startAt: agreement?.startAt ?? '',
        status: agreement?.status ?? 'draft',
    }));
    const [error, setError] = useState<string>();
    const [saving, setSaving] = useState(false);

    function update<K extends keyof VehicleRentalAgreementFormInput>(key: K, value: VehicleRentalAgreementFormInput[K]) {
        setForm((current) => ({ ...current, [key]: value }));
    }

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        setError(undefined);
        try {
            const response = await vehicleRentalApi.agreements.createLinked(form);
            navigate(`/vehicle-rental/agreements/${response.data.lesseeAgreement.id}`);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Vehicle rental agreement save failed.');
        } finally {
            setSaving(false);
        }
    }

    const tabs = [
        { label: 'Header / Rental Details', value: 'header' },
        { label: 'Lessee Agreement', value: 'lessee' },
        { label: 'Lessor Agreement', value: 'lessor' },
        { label: 'Review', value: 'review' },
    ];

    return (
        <form className="space-y-5" onSubmit={(event) => void submit(event)}>
            <Card className="p-4"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} trailing={<StatusBadge status="dual agreement" />} /></Card>
            {error ? <EmptyState description={error} title="Save failed" /> : null}
            {activeTab === 'header' ? (
                <FormSection description="Rental number and agreement document numbers are generated by backend sequences when blank." title="Header / Rental Details">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="Vehicle"><Select onFocus={() => void lookups.load('vehicles')} onMouseDown={() => void lookups.load('vehicles')} onChange={(event) => update('rentalVehicleId', event.target.value)} options={toSelectOptions(lookups.vehicles)} placeholder="Search/select rental vehicle" value={form.rentalVehicleId} /></Field>
                        <Field label="Rental start"><Input onChange={(event) => update('startAt', event.target.value)} type="datetime-local" value={form.startAt} /></Field>
                        <Field label="Rental end"><Input onChange={(event) => update('endAt', event.target.value)} type="datetime-local" value={form.endAt} /></Field>
                        <Field label="Pickup location"><Input onChange={(event) => update('pickupLocation', event.target.value)} value={form.pickupLocation} /></Field>
                        <Field label="Return location"><Input onChange={(event) => update('returnLocation', event.target.value)} value={form.returnLocation} /></Field>
                        <Field label="Driver mode"><Select onChange={(event) => update('driverMode', event.target.value as VehicleRentalAgreementFormInput['driverMode'])} options={[{ label: 'Without driver', value: 'without_driver' }, { label: 'With driver', value: 'with_driver' }]} value={form.driverMode} /></Field>
                        <Field label="Rate model"><Select onChange={(event) => update('rateModel', event.target.value)} options={[{ label: 'Day', value: 'day' }, { label: 'Hour', value: 'hour' }, { label: 'KM', value: 'km' }, { label: 'Month', value: 'month' }, { label: 'Fixed', value: 'fixed' }]} value={form.rateModel} /></Field>
                        <Field label="Allowed daily hours"><Input onChange={(event) => update('allowedDailyHours', event.target.value)} type="number" value={form.allowedDailyHours} /></Field>
                        <Field label="Allowed daily km"><Input onChange={(event) => update('allowedDailyKm', event.target.value)} type="number" value={form.allowedDailyKm} /></Field>
                    </div>
                </FormSection>
            ) : null}
            {activeTab === 'lessee' ? (
                <FormSection description="Customer-facing contract, receivable rates, terms, invoice, payment, and refund references stay on the lessee side." title="Lessee Agreement">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="Lessee / customer"><Select onFocus={() => void lookups.load('customers')} onMouseDown={() => void lookups.load('customers')} onChange={(event) => update('customerId', event.target.value)} options={toSelectOptions(lookups.customers)} placeholder="Select customer" value={form.customerId} /></Field>
                        <Field label="Lessee agreement number"><Input onChange={(event) => update('lesseeAgreementNumber', event.target.value)} placeholder="Backend generated if blank" value={form.lesseeAgreementNumber} /></Field>
                        <Field label="Lessee base rate"><Input onChange={(event) => update('lesseeBaseRate', event.target.value)} type="number" value={form.lesseeBaseRate} /></Field>
                        <Field label="Deposit / advance"><Input onChange={(event) => update('depositAmount', event.target.value)} type="number" value={form.depositAmount} /></Field>
                        <div className="md:col-span-2 xl:col-span-3"><Field label="Lessee terms"><Textarea onChange={(event) => update('lesseeTerms', event.target.value)} value={form.lesseeTerms} /></Field></div>
                    </div>
                </FormSection>
            ) : null}
            {activeTab === 'lessor' ? (
                <FormSection description="Provider-facing contract, payable rates, deductions, terms, settlement, and payable references stay on the lessor side." title="Lessor Agreement">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="Lessor type"><Select onChange={(event) => update('lessorPartyType', event.target.value as VehicleRentalAgreementFormInput['lessorPartyType'])} options={[{ label: 'Supplier/provider', value: 'supplier' }, { label: 'Customer owner', value: 'customer' }, { label: 'Company/internal', value: 'company' }, { label: 'External party', value: 'external_party' }]} value={form.lessorPartyType} /></Field>
                        {form.lessorPartyType === 'supplier' ? <Field label="Supplier / provider"><Select onFocus={() => void lookups.load('providers')} onMouseDown={() => void lookups.load('providers')} onChange={(event) => { update('providerId', event.target.value); update('lessorPartyId', event.target.value); }} options={toSelectOptions(lookups.providers)} placeholder="Select provider" value={form.providerId} /></Field> : <Field label="Lessor name"><Input onChange={(event) => update('lessorPartyName', event.target.value)} value={form.lessorPartyName} /></Field>}
                        <Field label="Lessor agreement number"><Input onChange={(event) => update('lessorAgreementNumber', event.target.value)} placeholder="Backend generated if blank" value={form.lessorAgreementNumber} /></Field>
                        <Field label="Lessor base rate"><Input onChange={(event) => update('lessorBaseRate', event.target.value)} type="number" value={form.lessorBaseRate} /></Field>
                        <div className="md:col-span-2 xl:col-span-3"><Field label="Lessor terms / deductions"><Textarea onChange={(event) => update('lessorTerms', event.target.value)} value={form.lessorTerms} /></Field></div>
                    </div>
                </FormSection>
            ) : null}
            {activeTab === 'review' ? (
                <div className="grid gap-5 xl:grid-cols-3">
                    <PreviewPanel rows={[{ label: 'Customer receivable side', value: form.customerId ? 'Ready' : 'Missing lessee' }, { label: 'Provider payable side', value: form.lessorPartyType === 'supplier' ? (form.providerId ? 'Ready' : 'Missing provider') : (form.lessorPartyName ? 'Ready' : 'Missing lessor name') }]} status="Backend save" title="Dual Agreement Readiness" />
                    <PreviewPanel rows={[{ label: 'Lessee rate', value: form.lesseeBaseRate || '0' }, { label: 'Lessor rate', value: form.lessorBaseRate || '0' }]} status="Separate rates" title="Rate Separation" />
                    <PreviewPanel rows={[{ label: 'Vehicle', value: form.rentalVehicleId || 'Not selected' }, { label: 'Period', value: `${form.startAt || 'Not set'} -> ${form.endAt || 'Open'}` }]} status="Header" title="Rental Details" />
                </div>
            ) : null}
            <div className="flex justify-end gap-2">
                <Button disabled={saving} type="submit" variant="blue">{saving ? 'Saving...' : 'Create Linked Agreements'}</Button>
            </div>
        </form>
    );
}

const emptyRunningForm: VehicleRentalRunningChartFormInput = {
    date: today(),
    deductions: '0',
    driverCharges: '0',
    driverId: '',
    durationHours: '0',
    endMeter: '0',
    extraCharges: '0',
    fuel: '0',
    lesseeAgreementId: '',
    lessorAgreementId: '',
    mileageCharges: '0',
    notes: '',
    rentalVehicleId: '',
    runningDistance: '0',
    startMeter: '0',
};

export function RunningChartForm() {
    const navigate = useNavigate();
    const lookups = useVehicleRentalLookups();
    const [form, setForm] = useState(emptyRunningForm);
    const [error, setError] = useState<string>();
    const [saving, setSaving] = useState(false);

    function update<K extends keyof VehicleRentalRunningChartFormInput>(key: K, value: VehicleRentalRunningChartFormInput[K]) {
        setForm((current) => ({ ...current, [key]: value }));
    }

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        setError(undefined);
        try {
            const response = await vehicleRentalApi.runningCharts.createCombined(form);
            navigate(`/vehicle-rental/running-charts/${response.data.lesseeRunningChart.id}`);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Running chart save failed.');
        } finally {
            setSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void submit(event)}>
            {error ? <EmptyState description={error} title="Running chart failed" /> : null}
            <FormSection description="Select both sides once. Backend writes separate lessee and lessor running chart records and calculates each side independently." title="Running Chart Entry">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Field label="Lessee agreement"><Select onFocus={() => void lookups.load('lesseeAgreements')} onMouseDown={() => void lookups.load('lesseeAgreements')} onChange={(event) => update('lesseeAgreementId', event.target.value)} options={toSelectOptions(lookups.lesseeAgreements)} placeholder="Select lessee agreement" value={form.lesseeAgreementId} /></Field>
                    <Field label="Lessor agreement"><Select onFocus={() => void lookups.load('lessorAgreements')} onMouseDown={() => void lookups.load('lessorAgreements')} onChange={(event) => update('lessorAgreementId', event.target.value)} options={toSelectOptions(lookups.lessorAgreements)} placeholder="Select lessor agreement" value={form.lessorAgreementId} /></Field>
                    <Field label="Vehicle"><Select onFocus={() => void lookups.load('vehicles')} onMouseDown={() => void lookups.load('vehicles')} onChange={(event) => update('rentalVehicleId', event.target.value)} options={toSelectOptions(lookups.vehicles)} value={form.rentalVehicleId} /></Field>
                    <Field label="Date"><Input onChange={(event) => update('date', event.target.value)} type="date" value={form.date} /></Field>
                    <Field label="Start meter"><Input onChange={(event) => update('startMeter', event.target.value)} type="number" value={form.startMeter} /></Field>
                    <Field label="End meter"><Input onChange={(event) => update('endMeter', event.target.value)} type="number" value={form.endMeter} /></Field>
                    <Field label="Running distance"><Input onChange={(event) => update('runningDistance', event.target.value)} type="number" value={form.runningDistance} /></Field>
                    <Field label="Duration hours"><Input onChange={(event) => update('durationHours', event.target.value)} type="number" value={form.durationHours} /></Field>
                    <Field label="Fuel"><Input onChange={(event) => update('fuel', event.target.value)} type="number" value={form.fuel} /></Field>
                    <Field label="Driver charges"><Input onChange={(event) => update('driverCharges', event.target.value)} type="number" value={form.driverCharges} /></Field>
                    <Field label="Mileage charges"><Input onChange={(event) => update('mileageCharges', event.target.value)} type="number" value={form.mileageCharges} /></Field>
                    <Field label="Deductions"><Input onChange={(event) => update('deductions', event.target.value)} type="number" value={form.deductions} /></Field>
                    <div className="md:col-span-2 xl:col-span-3"><Field label="Notes"><Textarea onChange={(event) => update('notes', event.target.value)} value={form.notes} /></Field></div>
                </div>
            </FormSection>
            <div className="flex justify-end"><Button disabled={saving} type="submit" variant="blue">{saving ? 'Saving...' : 'Save Both Running Charts'}</Button></div>
        </form>
    );
}

export function RentalBillingPreviewPanel({ agreement }: { agreement: VehicleRentalAgreement }) {
    return <PreviewPanel rows={[
        { label: 'Lessee receivable', value: agreement.billingPreview.calculated.grandTotal },
        { label: 'Lessor payable', value: agreement.billingPreview.calculated.providerPayable },
        { label: 'Tax', value: agreement.billingPreview.calculated.taxTotal },
    ]} status={agreement.agreementRole} title={agreement.agreementRole === 'lessee' ? 'Lessee Billing Preview' : 'Lessor Payable Preview'} />;
}

export function RentalProviderPanel({ agreement }: { agreement: VehicleRentalAgreement }) {
    return <PreviewPanel rows={[
        { label: 'Lessor / provider', value: agreement.provider },
        { label: 'Linked lessor agreement', value: agreement.lessorAgreementId ?? 'Not linked' },
        { label: 'Provider payable', value: agreement.billingPreview.calculated.providerPayable },
    ]} status="Lessor" title="Lessor / Provider" />;
}

export function RentalRateRulesTable({ agreement }: { agreement: VehicleRentalAgreement }) {
    return <DataTable columns={[{ header: 'Rule', key: 'ruleName' }, { header: 'Type', key: 'ruleType' }, { header: 'Scope', key: 'scope' }, { header: 'Backend value', key: 'valuePreview' }]} getRowKey={(row) => `rental-rule-${row.id}`} rows={agreement.rateRules} />;
}

export function RunningChartLineTable({ chart }: { chart: VehicleRentalRunningChart }) {
    return <DataTable columns={[
        { header: 'Line', key: 'lineNumber' },
        { header: 'Side', key: 'side', render: () => <StatusBadge status={chart.side} /> },
        { header: 'Vehicle', key: 'vehicle' },
        { header: 'Start', key: 'startReading' },
        { header: 'End', key: 'endReading' },
        { header: 'Usage', key: 'usagePreview' },
        { header: 'Lessee charge', key: 'chargePreview' },
        { header: 'Lessor payable', key: 'providerCostPreview' },
    ]} getRowKey={(row) => `running-line-${chart.side}-${row.id}`} rows={chart.lines} />;
}

export function RunningChartBillingPreviewPanel({ chart }: { chart: VehicleRentalRunningChart }) {
    return <PreviewPanel rows={[
        { label: 'Side', value: chart.side },
        { label: 'Lessee charge', value: chart.billingPreview.calculated.rentalCharge },
        { label: 'Lessor payable', value: chart.providerPayablePreview },
        { label: 'Grand total', value: chart.billingPreview.calculated.grandTotal },
    ]} status="Backend Preview" title="Running Chart Preview" />;
}

export function RentalProviderPayablePanel({ rows }: { rows: VehicleRentalProviderPayable[] }) {
    return <DataTable columns={[
        { header: 'Payable', key: 'payableNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-rental/provider-payables/${row.id}`}>{row.payableNumber}</Link> },
        { header: 'Lessor agreement', key: 'agreementNumber' },
        { header: 'Provider', key: 'provider' },
        { header: 'Payable', key: 'payablePreview' },
        { header: 'Payment', key: 'paymentStatus' },
        { header: 'Finance', key: 'financeStatus' },
        { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
    ]} getRowKey={(row) => `provider-payable-${row.id}`} rows={rows} />;
}

export function RentalInvoicePanel({ rows }: { rows: VehicleRentalInvoice[] }) {
    return <DataTable columns={[
        { header: 'Invoice', key: 'invoiceNumber' },
        { header: 'Lessee agreement', key: 'sourceAgreement' },
        { header: 'Customer', key: 'customer' },
        { header: 'Receivable', key: 'billingPreview' },
        { header: 'Document', key: 'documentStatus' },
        { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
    ]} getRowKey={(row) => `rental-invoice-${row.id}`} rows={rows} />;
}

export function RentalPaymentPanel({ rows }: { rows: VehicleRentalPayment[] }) {
    return <DataTable columns={[{ header: 'Payment', key: 'paymentNumber' }, { header: 'Customer', key: 'customer' }, { header: 'Amount', key: 'amount' }, { header: 'Status', key: 'status' }]} getRowKey={(row) => `rental-payment-${row.id}`} rows={rows} />;
}

export function ReplacementVehiclePanel({ rows }: { rows: VehicleRentalReplacement[] }) {
    return <DataTable columns={[{ header: 'Replacement', key: 'replacementNumber' }, { header: 'Agreement', key: 'agreementNumber' }, { header: 'Original', key: 'originalVehicle' }, { header: 'Replacement', key: 'replacementVehicle' }, { header: 'Status', key: 'status' }]} getRowKey={(row) => `replacement-${row.id}`} rows={rows} />;
}

export function BreakdownPanel({ rows }: { rows: VehicleRentalBreakdown[] }) {
    return <DataTable columns={[{ header: 'Breakdown', key: 'breakdownNumber' }, { header: 'Agreement', key: 'agreementNumber' }, { header: 'Vehicle', key: 'vehicle' }, { header: 'Status', key: 'status' }]} getRowKey={(row) => `breakdown-${row.id}`} rows={rows} />;
}

export function VehicleRentalFinancePostingPanel({ agreement }: { agreement: VehicleRentalAgreement }) {
    return <PreviewPanel rows={[
        { label: 'AR impact', value: agreement.financePreview.calculated.arImpact },
        { label: 'AP impact', value: agreement.financePreview.calculated.apImpact },
        { label: 'Eligibility', value: agreement.financePreview.calculated.eligibility },
        { label: 'Journal status', value: agreement.financePreview.calculated.journalStatus },
    ]} status="Finance" title="Finance Posting" />;
}

export function VehicleRentalWorkflowActions({ agreement }: { agreement: VehicleRentalAgreement }) {
    const [busy, setBusy] = useState<string>();
    const [message, setMessage] = useState<string>();

    async function run(action: string, callback: () => Promise<unknown>) {
        setBusy(action);
        setMessage(undefined);
        try {
            await callback();
            setMessage(`${action} submitted to backend.`);
        } catch (caught) {
            setMessage(caught instanceof Error ? caught.message : `${action} failed.`);
        } finally {
            setBusy(undefined);
        }
    }

    return (
        <Card className="p-5">
            <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">Workflow Actions</h3>
            <div className="mt-4 flex flex-wrap gap-2">
                {agreement.agreementRole === 'lessee' ? <Button disabled={Boolean(busy)} onClick={() => void run('Lessee invoice', () => vehicleRentalApi.invoices.generateLessee(agreement.id))} variant="secondary">Generate Lessee Invoice</Button> : null}
                {agreement.agreementRole === 'lessor' ? <Button disabled={Boolean(busy)} onClick={() => void run('Lessor payable', () => vehicleRentalApi.providerPayables.create(agreement.id))} variant="secondary">Create Lessor Payable</Button> : null}
                <Button disabled={Boolean(busy)} onClick={() => void run('Finance post', () => vehicleRentalApi.finance.post('agreement', agreement.id))} variant="secondary">Post Finance</Button>
                <Button disabled={Boolean(busy)} onClick={() => void run('Cancel', () => vehicleRentalApi.agreements.transition(agreement.id, 'cancelled'))} variant="danger">Cancel</Button>
            </div>
            {message ? <p className="mt-4 text-sm font-semibold text-slate-600">{message}</p> : null}
            <p className="mt-4 text-sm font-semibold text-slate-600">Current backend status: {agreement.workflowStatus}</p>
        </Card>
    );
}

export function VehicleRentalActivityTimeline({ rows }: { rows: VehicleRentalAuditEntry[] }) {
    if (!rows.length) return <EmptyState description="No history returned for this rental record yet." title="No activity" />;
    return <div className="space-y-3">{rows.map((entry) => <Card className="p-4" key={`rental-activity-${entry.id}`}><p className="font-semibold text-slate-900">{entry.note}</p><p className="mt-1 text-sm text-slate-500">{entry.actor} - {entry.timestamp}</p></Card>)}</div>;
}

export function VehicleRentalSettingsForm({ settings }: { settings: VehicleRentalSettings }) {
    return <FormSection description="Module settings for rental sequences, document definitions, provider flow, and default financial mappings." title="Rental Settings">
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Field label="Agreement sequence"><Input readOnly value={settings.agreementSequence} /></Field>
            <Field label="Running chart sequence"><Input readOnly value={settings.runningChartSequence} /></Field>
            <Field label="Invoice sequence"><Input readOnly value={settings.invoiceSequence} /></Field>
            <Field label="Default rate plan"><Input readOnly value={settings.defaultRatePlan} /></Field>
            <Field label="Default tax group"><Input readOnly value={settings.defaultTaxGroup} /></Field>
            <Field label="Provider payable account"><Input readOnly value={settings.defaultProviderPayableAccount} /></Field>
        </div>
    </FormSection>;
}

