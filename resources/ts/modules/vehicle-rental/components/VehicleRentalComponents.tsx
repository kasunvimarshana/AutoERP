import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type {
    VehicleRentalAgreement,
    VehicleRentalAgreementFormInput,
    VehicleRentalAgreementLine,
    VehicleRentalAgreementLineInput,
    VehicleRentalAuditEntry,
    VehicleRentalBreakdown,
    VehicleRentalDashboardMetric,
    VehicleRentalInvoice,
    VehicleRentalLookupOption,
    VehicleRentalPayment,
    VehicleRentalPaymentFormInput,
    VehicleRentalProviderPayable,
    VehicleRentalReplacement,
    VehicleRentalRunningChart,
    VehicleRentalRunningChartFormInput,
    VehicleRentalSettings,
} from '../types/vehicleRental.types';

function asErrorMessage(error: unknown): string {
    if (error instanceof ApiError || error instanceof Error) {
        return error.message;
    }

    return 'Vehicle Rental request failed.';
}

function fieldErrors(error: unknown): Record<string, string[]> {
    return error instanceof ApiError ? error.errors : {};
}

function optionList(options: VehicleRentalLookupOption[]) {
    return options.map((option) => ({ label: option.secondary ? `${option.label} (${option.secondary})` : option.label, value: option.id }));
}

function labelFor(options: VehicleRentalLookupOption[], id?: string): string | undefined {
    return options.find((option) => option.id === id)?.label;
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

function emptyLine(lineType = 'base_rental'): VehicleRentalAgreementLineInput {
    return {
        billingBasis: 'day',
        chargeScope: 'customer',
        description: '',
        itemId: '',
        lineType,
        quantity: '1',
        unitRate: '0',
        uomId: '',
    };
}

function emptyAgreement(): VehicleRentalAgreementFormInput {
    return {
        agreementDate: today(),
        agreementNumber: '',
        billingFrequency: 'day',
        customerId: '',
        depositAmount: '0',
        driverId: '',
        driverMode: 'without_driver',
        endAt: '',
        kilometerLimit: '0',
        lines: [emptyLine()],
        notes: '',
        providerId: '',
        rateModel: 'daily',
        rentalVehicleId: '',
        startAt: '',
        status: 'draft',
    };
}

function agreementToForm(agreement: VehicleRentalAgreement): VehicleRentalAgreementFormInput {
    return {
        ...emptyAgreement(),
        agreementNumber: agreement.agreementNumber,
        billingFrequency: agreement.rentalUnit,
        driverMode: agreement.mode,
        endAt: agreement.endAt,
        lines: agreement.lines.length ? agreement.lines.map(lineToInput) : [emptyLine()],
        notes: agreement.sourceReference.sourceNumber ?? '',
        rateModel: agreement.ratePlan.rentalUnit || 'daily',
        startAt: agreement.startAt,
        status: agreement.status,
    };
}

function lineToInput(line: VehicleRentalAgreementLine): VehicleRentalAgreementLineInput {
    return {
        billingBasis: line.rentalUnit,
        chargeScope: line.chargeScope,
        description: line.description,
        id: line.id,
        itemId: '',
        lineType: 'base_rental',
        quantity: '1',
        unitRate: line.backendAmount,
        uomId: '',
    };
}

function emptyChart(): VehicleRentalRunningChartFormInput {
    return {
        agreementId: '',
        chartDate: today(),
        chartNumber: '',
        driverId: '',
        endAt: '',
        notes: '',
        rentalVehicleId: '',
        startAt: '',
        status: 'draft',
    };
}

function Field({ children, error, label }: { children: ReactNode; error?: string[]; label: string }) {
    return (
        <label className="space-y-2 text-sm">
            <span className="font-semibold text-slate-700">{label}</span>
            {children}
            {error?.length ? <span className="block text-xs font-semibold text-red-600">{error.join(' ')}</span> : null}
        </label>
    );
}

function FormError({ error }: { error?: string }) {
    return error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{error}</div> : null;
}

function useLookups() {
    const [customers, setCustomers] = useState<VehicleRentalLookupOption[]>([]);
    const [drivers, setDrivers] = useState<VehicleRentalLookupOption[]>([]);
    const [items, setItems] = useState<VehicleRentalLookupOption[]>([]);
    const [providers, setProviders] = useState<VehicleRentalLookupOption[]>([]);
    const [rentalVehicles, setRentalVehicles] = useState<VehicleRentalLookupOption[]>([]);

    useEffect(() => {
        Promise.all([
            vehicleRentalApi.lookups.customers(),
            vehicleRentalApi.lookups.employees(),
            vehicleRentalApi.lookups.items(),
            vehicleRentalApi.lookups.suppliers(),
            vehicleRentalApi.lookups.rentalVehicles(),
        ]).then(([customerResponse, employeeResponse, itemResponse, supplierResponse, vehicleResponse]) => {
            setCustomers(customerResponse.data);
            setDrivers(employeeResponse.data);
            setItems(itemResponse.data);
            setProviders(supplierResponse.data);
            setRentalVehicles(vehicleResponse.data);
        }).catch(() => undefined);
    }, []);

    return { customers, drivers, items, providers, rentalVehicles };
}

export function VehicleRentalPageHeader({ actions, subtitle, title }: { actions?: ReactNode; subtitle?: string; title: string }) {
    return <PageHeader actions={actions} eyebrow="Vehicle Rental" subtitle={subtitle} title={title} />;
}

export function VehicleRentalDashboardCards({ metrics }: { metrics: VehicleRentalDashboardMetric[] }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            {metrics.map((metric) => (
                <Card className="p-5" key={metric.label}>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{metric.label}</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{metric.value}</p>
                    <p className="mt-2 text-xs font-semibold text-slate-500">{metric.tone}</p>
                </Card>
            ))}
        </div>
    );
}

export function VehicleAvailabilityForm() {
    const lookups = useLookups();
    const [form, setForm] = useState({ end_datetime: '', rental_vehicle_id: '', start_datetime: '' });
    const [preview, setPreview] = useState<{ availabilityDecision: string; conflicts: string; replacementOption: string; vehicleStatus: string }>();
    const [error, setError] = useState<string>();
    const [loading, setLoading] = useState(false);

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setLoading(true);
        setError(undefined);
        try {
            const response = await vehicleRentalApi.availability.preview(form);
            setPreview(response.calculated);
        } catch (caught) {
            setError(asErrorMessage(caught));
        } finally {
            setLoading(false);
        }
    }

    return (
        <form className="space-y-4" onSubmit={submit}>
            <FormError error={error} />
            <FormSection description="Backend validates rental availability, date overlaps, and rental vehicle eligibility." title="Availability Preview">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Field label="Rental vehicle"><Select onChange={(event) => setForm((current) => ({ ...current, rental_vehicle_id: event.target.value }))} options={optionList(lookups.rentalVehicles)} required value={form.rental_vehicle_id} /></Field>
                    <Field label="Start"><Input onChange={(event) => setForm((current) => ({ ...current, start_datetime: event.target.value }))} required type="datetime-local" value={form.start_datetime} /></Field>
                    <Field label="End"><Input onChange={(event) => setForm((current) => ({ ...current, end_datetime: event.target.value }))} type="datetime-local" value={form.end_datetime} /></Field>
                    <div className="flex items-end"><Button disabled={loading} type="submit" variant="blue">{loading ? 'Checking...' : 'Preview Availability'}</Button></div>
                </div>
            </FormSection>
            {preview ? <PreviewPanel rows={[
                { label: 'Decision', value: preview.availabilityDecision },
                { label: 'Conflicts', value: preview.conflicts },
                { label: 'Replacement option', value: preview.replacementOption },
                { label: 'Vehicle status', value: preview.vehicleStatus },
            ]} status="Backend" title="Availability Result" /> : null}
        </form>
    );
}

export function VehicleAvailabilityTable({ rows }: { rows: Array<{ availability: string; decision: string; id: string; source: string; vehicle: string; window: string }> }) {
    return <DataTable columns={[{ header: 'Vehicle', key: 'vehicle' }, { header: 'Source', key: 'source' }, { header: 'Availability', key: 'availability', render: (row) => <StatusBadge status={row.availability} /> }, { header: 'Window', key: 'window' }, { header: 'Backend decision', key: 'decision' }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function RentalAgreementTable({ rows }: { rows: VehicleRentalAgreement[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Agreement', key: 'agreementNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-rental/agreements/${row.id}`}>{row.agreementNumber}</Link> },
                { header: 'Customer', key: 'customer' },
                { header: 'Vehicle', key: 'vehicle' },
                { header: 'Mode', key: 'mode' },
                { header: 'Provider', key: 'provider' },
                { header: 'Rental unit', key: 'rentalUnit' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <Link to={`/vehicle-rental/agreements/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

function RentalLineEditor({ items, line, onChange }: { items: VehicleRentalLookupOption[]; line: VehicleRentalAgreementLineInput; onChange: (line: VehicleRentalAgreementLineInput) => void }) {
    const [uoms, setUoms] = useState<VehicleRentalLookupOption[]>([]);

    useEffect(() => {
        if (!line.itemId) {
            setUoms([]);
            return;
        }

        vehicleRentalApi.lookups.itemUnits(line.itemId).then((response) => {
            setUoms(response.data);
            if (line.uomId && !response.data.some((unit) => unit.id === line.uomId)) {
                onChange({ ...line, uomId: response.data[0]?.id ?? '' });
            } else if (!line.uomId && response.data[0]) {
                onChange({ ...line, uomId: response.data[0].id });
            }
        }).catch(() => setUoms([]));
    }, [line.itemId]);

    return (
        <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 md:grid-cols-[140px_1fr_120px_120px_120px_1fr]">
            <Select onChange={(event) => onChange({ ...line, lineType: event.target.value })} options={[{ label: 'Base rental', value: 'base_rental' }, { label: 'Driver', value: 'driver' }, { label: 'Deposit', value: 'deposit' }, { label: 'Extra charge', value: 'extra_charge' }, { label: 'Accessory', value: 'accessory' }]} value={line.lineType} />
            <Select onChange={(event) => onChange({ ...line, itemId: event.target.value, uomId: '' })} options={optionList(items)} placeholder="Select rental charge item" value={line.itemId} />
            <Input min="0" onChange={(event) => onChange({ ...line, quantity: event.target.value })} type="number" value={line.quantity} />
            <Select disabled={!line.itemId} onChange={(event) => onChange({ ...line, uomId: event.target.value })} options={optionList(uoms)} placeholder={line.itemId ? 'Item UOM' : 'Select item first'} value={line.uomId} />
            <Input min="0" onChange={(event) => onChange({ ...line, unitRate: event.target.value })} type="number" value={line.unitRate} />
            <Input onChange={(event) => onChange({ ...line, description: event.target.value })} placeholder="Description" value={line.description} />
        </div>
    );
}

export function RentalAgreementForm({ agreement, mode = 'create' }: { agreement?: VehicleRentalAgreement; mode?: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const lookups = useLookups();
    const [activeTab, setActiveTab] = useState('customer');
    const [form, setForm] = useState<VehicleRentalAgreementFormInput>(() => agreement ? agreementToForm(agreement) : emptyAgreement());
    const [error, setError] = useState<string>();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (agreement) {
            setForm(agreementToForm(agreement));
        }
    }, [agreement?.id]);

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setSaving(true);
        setError(undefined);
        setErrors({});

        try {
            const response = mode === 'edit' && agreement
                ? await vehicleRentalApi.agreements.update(agreement.id, form)
                : await vehicleRentalApi.agreements.create(form);
            navigate(`/vehicle-rental/agreements/${response.data.id}`);
        } catch (caught) {
            setError(asErrorMessage(caught));
            setErrors(fieldErrors(caught));
        } finally {
            setSaving(false);
        }
    }

    function updateLine(index: number, line: VehicleRentalAgreementLineInput): void {
        setForm((current) => ({ ...current, lines: current.lines.map((existing, currentIndex) => currentIndex === index ? line : existing) }));
    }

    return (
        <form className="space-y-5" onSubmit={submit}>
            <FormError error={error} />
            <Card className="p-5">
                <Tabs active={activeTab} items={[
                    { label: 'Customer & Vehicle', value: 'customer' },
                    { label: 'Rental Period & Availability', value: 'period' },
                    { label: 'Rates / Charges', value: 'rates' },
                    { label: 'Provider / Driver', value: 'provider' },
                    { label: 'Review', value: 'review' },
                ]} onChange={setActiveTab} trailing={<StatusBadge status="Real backend" />} />
            </Card>

            {activeTab === 'customer' ? (
                <FormSection description="Backend validates customer, rental vehicle, ownership/provider context, and tenant safety." title="Header / Customer & Vehicle">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field error={errors.agreement_number} label="Agreement number"><Input onChange={(event) => setForm((current) => ({ ...current, agreementNumber: event.target.value }))} placeholder="Leave blank for draft sequence" value={form.agreementNumber} /></Field>
                        <Field error={errors.customer_id} label="Rental customer"><Select onChange={(event) => setForm((current) => ({ ...current, customerId: event.target.value }))} options={optionList(lookups.customers)} placeholder="Select customer" value={form.customerId} /></Field>
                        <Field error={errors.rental_vehicle_id} label="Rental vehicle"><Select onChange={(event) => setForm((current) => ({ ...current, rentalVehicleId: event.target.value }))} options={optionList(lookups.rentalVehicles)} placeholder="Select rental vehicle" required value={form.rentalVehicleId} /></Field>
                        <Field error={errors.status} label="Status"><Select onChange={(event) => setForm((current) => ({ ...current, status: event.target.value as VehicleRentalAgreementFormInput['status'] }))} options={[{ label: 'Draft', value: 'draft' }, { label: 'Active', value: 'active' }, { label: 'Running', value: 'running' }, { label: 'Invoiceable', value: 'invoiceable' }, { label: 'Closed', value: 'closed' }]} value={form.status} /></Field>
                    </div>
                </FormSection>
            ) : null}

            {activeTab === 'period' ? (
                <FormSection description="Frontend collects dates only. Backend owns availability, duration, mileage, fuel and overlap rules." title="Rental Period & Availability">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Field error={errors.agreement_date} label="Agreement date"><Input onChange={(event) => setForm((current) => ({ ...current, agreementDate: event.target.value }))} type="date" value={form.agreementDate} /></Field>
                        <Field error={errors.start_datetime} label="Start"><Input onChange={(event) => setForm((current) => ({ ...current, startAt: event.target.value }))} type="datetime-local" value={form.startAt} /></Field>
                        <Field error={errors.end_datetime} label="End"><Input onChange={(event) => setForm((current) => ({ ...current, endAt: event.target.value }))} type="datetime-local" value={form.endAt} /></Field>
                        <Field label="Rate model"><Select onChange={(event) => setForm((current) => ({ ...current, rateModel: event.target.value }))} options={[{ label: 'Daily', value: 'daily' }, { label: 'Hourly', value: 'hourly' }, { label: 'Monthly', value: 'monthly' }, { label: 'Kilometer', value: 'kilometer' }]} value={form.rateModel} /></Field>
                        <Field label="Billing frequency"><Select onChange={(event) => setForm((current) => ({ ...current, billingFrequency: event.target.value }))} options={[{ label: 'Day', value: 'day' }, { label: 'Hour', value: 'hour' }, { label: 'Month', value: 'month' }, { label: 'KM', value: 'km' }]} value={form.billingFrequency} /></Field>
                        <Field label="KM limit"><Input onChange={(event) => setForm((current) => ({ ...current, kilometerLimit: event.target.value }))} type="number" value={form.kilometerLimit} /></Field>
                    </div>
                </FormSection>
            ) : null}

            {activeTab === 'rates' ? (
                <FormSection description="Item, UOM and rate inputs are saved to backend. Backend owns rental charge, tax, discount and provider payable calculations." title="Rates / Charges">
                    <div className="space-y-3">
                        {form.lines.map((line, index) => <RentalLineEditor items={lookups.items} key={`line-${index}`} line={line} onChange={(next) => updateLine(index, next)} />)}
                        <Button onClick={() => setForm((current) => ({ ...current, lines: [...current.lines, emptyLine('extra_charge')] }))} type="button" variant="secondary">Add Charge Line</Button>
                    </div>
                </FormSection>
            ) : null}

            {activeTab === 'provider' ? (
                <FormSection description="Provider/payee may differ from rental customer. Driver and provider are optional backend-validated links." title="Provider / Driver">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Rental mode"><Select onChange={(event) => setForm((current) => ({ ...current, driverMode: event.target.value as VehicleRentalAgreementFormInput['driverMode'] }))} options={[{ label: 'Without driver', value: 'without_driver' }, { label: 'With driver', value: 'with_driver' }]} value={form.driverMode} /></Field>
                        <Field label="Driver"><Select onChange={(event) => setForm((current) => ({ ...current, driverId: event.target.value }))} options={optionList(lookups.drivers)} placeholder="Optional driver" value={form.driverId} /></Field>
                        <Field label="Provider / payee"><Select onChange={(event) => setForm((current) => ({ ...current, providerId: event.target.value }))} options={optionList(lookups.providers)} placeholder="Optional provider" value={form.providerId} /></Field>
                        <Field label="Deposit / advance"><Input onChange={(event) => setForm((current) => ({ ...current, depositAmount: event.target.value }))} type="number" value={form.depositAmount} /></Field>
                        <div className="md:col-span-2"><Field label="Terms / notes"><Textarea onChange={(event) => setForm((current) => ({ ...current, notes: event.target.value }))} value={form.notes} /></Field></div>
                    </div>
                </FormSection>
            ) : null}

            {activeTab === 'review' ? (
                <div className="grid gap-5 xl:grid-cols-3">
                    <PreviewPanel rows={[
                        { label: 'Customer', value: labelFor(lookups.customers, form.customerId) ?? 'Not selected' },
                        { label: 'Vehicle', value: labelFor(lookups.rentalVehicles, form.rentalVehicleId) ?? 'Not selected' },
                        { label: 'Provider', value: labelFor(lookups.providers, form.providerId) ?? 'Not selected' },
                        { label: 'Charge lines', value: String(form.lines.length) },
                    ]} status="Backend Save" title="Draft Summary" />
                    <PreviewPanel rows={[{ label: 'Billing', value: 'Preview available after save' }, { label: 'Availability', value: 'Use availability page before confirmation' }]} status="Backend-owned" title="Preview Contracts" />
                </div>
            ) : null}

            <div className="flex justify-end gap-2">
                <Link to="/vehicle-rental/agreements"><Button type="button" variant="secondary">Cancel</Button></Link>
                <Button disabled={saving} type="submit" variant="blue">{saving ? 'Saving...' : mode === 'edit' ? 'Save Agreement' : 'Create Agreement'}</Button>
            </div>
        </form>
    );
}

export function RentalRateRulesTable({ agreement }: { agreement: VehicleRentalAgreement }) {
    return <DataTable columns={[{ header: 'Rule', key: 'ruleName' }, { header: 'Type', key: 'ruleType' }, { header: 'Scope', key: 'scope' }, { header: 'Backend value', key: 'valuePreview' }]} getRowKey={(row) => row.id} rows={agreement.rateRules} />;
}

export function RentalExtraChargesTable({ rows }: { rows: VehicleRentalAgreementLine[] }) {
    return <DataTable columns={[{ header: 'Charge', key: 'description' }, { header: 'Scope', key: 'chargeScope' }, { header: 'Usage basis', key: 'usageBasis' }, { header: 'Backend amount', key: 'backendAmount' }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function RentalBillingPreviewPanel({ agreement }: { agreement: VehicleRentalAgreement }) {
    return <PreviewPanel rows={[
        { label: 'Rental charge', value: agreement.billingPreview.calculated.rentalCharge },
        { label: 'Discount', value: agreement.billingPreview.calculated.discountTotal },
        { label: 'Tax', value: agreement.billingPreview.calculated.taxTotal },
        { label: 'Grand total', value: agreement.billingPreview.calculated.grandTotal },
        { label: 'Provider payable', value: agreement.billingPreview.calculated.providerPayable },
    ]} status="Backend Preview" title="Rental Billing Preview" />;
}

export function RentalProviderPayablePanel({ rows }: { rows: VehicleRentalProviderPayable[] }) {
    return <DataTable columns={[{ header: 'Payable', key: 'payableNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-rental/provider-payables/${row.id}`}>{row.payableNumber}</Link> }, { header: 'Agreement', key: 'agreementNumber' }, { header: 'Provider', key: 'provider' }, { header: 'Payable preview', key: 'payablePreview' }, { header: 'Payment', key: 'paymentStatus' }, { header: 'Finance', key: 'financeStatus' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function RentalProviderPanel({ agreement }: { agreement: VehicleRentalAgreement }) {
    return <PreviewPanel rows={[
        { label: 'Vehicle source', value: agreement.vehicleSource },
        { label: 'Provider', value: agreement.provider },
        { label: 'Provider payable', value: agreement.billingPreview.calculated.providerPayable },
        { label: 'AP impact', value: agreement.financePreview.calculated.apImpact },
    ]} status="Provider" title="Provider / Payee" />;
}

export function RunningChartTable({ rows }: { rows: VehicleRentalRunningChart[] }) {
    return <DataTable columns={[{ header: 'Chart', key: 'chartNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-rental/running-charts/${row.id}`}>{row.chartNumber}</Link> }, { header: 'Agreement', key: 'agreementNumber' }, { header: 'Vehicle', key: 'vehicle' }, { header: 'Driver', key: 'driver' }, { header: 'Customer bill', key: 'customerBill', render: (row) => row.billingPreview.calculated.grandTotal }, { header: 'Provider cost', key: 'providerPayablePreview' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function RunningChartLineTable({ chart }: { chart: VehicleRentalRunningChart }) {
    return <DataTable columns={[{ header: 'Line', key: 'lineNumber' }, { header: 'Vehicle', key: 'vehicle' }, { header: 'Driver', key: 'driver' }, { header: 'Start reading', key: 'startReading' }, { header: 'End reading', key: 'endReading' }, { header: 'Usage', key: 'usagePreview' }, { header: 'Charge', key: 'chargePreview' }, { header: 'Provider cost', key: 'providerCostPreview' }]} getRowKey={(row) => row.id} rows={chart.lines} />;
}

export function RunningChartForm({ chart, mode = 'create' }: { chart?: VehicleRentalRunningChart; mode?: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const lookups = useLookups();
    const [agreements, setAgreements] = useState<VehicleRentalAgreement[]>([]);
    const [form, setForm] = useState<VehicleRentalRunningChartFormInput>(() => chart ? { ...emptyChart(), chartNumber: chart.chartNumber, endAt: chart.endAt, startAt: chart.startAt, status: chart.status } : emptyChart());
    const [error, setError] = useState<string>();
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        vehicleRentalApi.agreements.list().then((response) => setAgreements(response.data)).catch(() => undefined);
    }, []);

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setSaving(true);
        setError(undefined);
        try {
            const response = mode === 'edit' && chart ? await vehicleRentalApi.runningCharts.update(chart.id, form) : await vehicleRentalApi.runningCharts.create(form);
            navigate(`/vehicle-rental/running-charts/${response.data.id}`);
        } catch (caught) {
            setError(asErrorMessage(caught));
        } finally {
            setSaving(false);
        }
    }

    return (
        <form className="space-y-4" onSubmit={submit}>
            <FormError error={error} />
            <FormSection description="Usage readings are collected here. Backend owns usage validation, billing and provider payable preview." title="Running Chart">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Field label="Agreement"><Select onChange={(event) => setForm((current) => ({ ...current, agreementId: event.target.value }))} options={agreements.map((agreement) => ({ label: `${agreement.agreementNumber} - ${agreement.customer}`, value: agreement.id }))} value={form.agreementId} /></Field>
                    <Field label="Chart number"><Input onChange={(event) => setForm((current) => ({ ...current, chartNumber: event.target.value }))} value={form.chartNumber} /></Field>
                    <Field label="Chart date"><Input onChange={(event) => setForm((current) => ({ ...current, chartDate: event.target.value }))} type="date" value={form.chartDate} /></Field>
                    <Field label="Vehicle"><Select onChange={(event) => setForm((current) => ({ ...current, rentalVehicleId: event.target.value }))} options={optionList(lookups.rentalVehicles)} value={form.rentalVehicleId} /></Field>
                    <Field label="Driver"><Select onChange={(event) => setForm((current) => ({ ...current, driverId: event.target.value }))} options={optionList(lookups.drivers)} value={form.driverId} /></Field>
                    <Field label="Status"><Select onChange={(event) => setForm((current) => ({ ...current, status: event.target.value }))} options={[{ label: 'Draft', value: 'draft' }, { label: 'Submitted', value: 'submitted' }, { label: 'Approved', value: 'approved' }]} value={form.status} /></Field>
                    <Field label="Start"><Input onChange={(event) => setForm((current) => ({ ...current, startAt: event.target.value }))} type="datetime-local" value={form.startAt} /></Field>
                    <Field label="End"><Input onChange={(event) => setForm((current) => ({ ...current, endAt: event.target.value }))} type="datetime-local" value={form.endAt} /></Field>
                    <div className="xl:col-span-3"><Field label="Notes"><Textarea onChange={(event) => setForm((current) => ({ ...current, notes: event.target.value }))} value={form.notes} /></Field></div>
                </div>
                <div className="mt-4 flex justify-end"><Button disabled={saving} type="submit" variant="blue">{saving ? 'Saving...' : 'Save Running Chart'}</Button></div>
            </FormSection>
        </form>
    );
}

export function RunningChartBillingPreviewPanel({ chart }: { chart: VehicleRentalRunningChart }) {
    return <PreviewPanel rows={[{ label: 'Rental charge', value: chart.billingPreview.calculated.rentalCharge }, { label: 'Tax', value: chart.billingPreview.calculated.taxTotal }, { label: 'Provider payable', value: chart.providerPayablePreview }, { label: 'Grand total', value: chart.billingPreview.calculated.grandTotal }]} status="Backend Preview" title="Running Chart Billing Preview" />;
}

export function RentalInvoicePanel({ rows }: { rows: VehicleRentalInvoice[] }) {
    return <DataTable columns={[{ header: 'Invoice', key: 'invoiceNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-rental/invoices/${row.id}`}>{row.invoiceNumber}</Link> }, { header: 'Agreement', key: 'sourceAgreement' }, { header: 'Customer', key: 'customer' }, { header: 'Billing', key: 'billingPreview' }, { header: 'Document', key: 'documentStatus' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function RentalPaymentPanel({ rows }: { rows: VehicleRentalPayment[] }) {
    return <DataTable columns={[{ header: 'Payment', key: 'paymentNumber' }, { header: 'Customer', key: 'customer' }, { header: 'Method', key: 'method' }, { header: 'Amount', key: 'amount' }, { header: 'Invoice / agreement', key: 'sourceInvoice' }, { header: 'Allocation', key: 'allocationPreview' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function RentalPaymentCreateForm() {
    const navigate = useNavigate();
    const [agreements, setAgreements] = useState<VehicleRentalAgreement[]>([]);
    const [form, setForm] = useState<VehicleRentalPaymentFormInput>({ agreementId: '', amount: '', documentId: '', documentType: 'document', paymentId: '' });
    const [preview, setPreview] = useState<VehicleRentalPayment>();
    const [error, setError] = useState<string>();
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        vehicleRentalApi.agreements.list().then((response) => setAgreements(response.data)).catch(() => undefined);
    }, []);

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setSaving(true);
        setError(undefined);
        try {
            await vehicleRentalApi.payments.create(form);
            navigate('/vehicle-rental/payments');
        } catch (caught) {
            setError(asErrorMessage(caught));
        } finally {
            setSaving(false);
        }
    }

    return (
        <form className="space-y-4" onSubmit={submit}>
            <FormError error={error} />
            <FormSection description="Allocates an existing core Payment to a rental agreement/document. Backend owns balances and AR posting." title="Rental payment allocation">
                <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Agreement"><Select onChange={(event) => setForm((current) => ({ ...current, agreementId: event.target.value }))} options={agreements.map((agreement) => ({ label: `${agreement.agreementNumber} - ${agreement.customer}`, value: agreement.id }))} value={form.agreementId} /></Field>
                    <Field label="Existing payment ID"><Input onChange={(event) => setForm((current) => ({ ...current, paymentId: event.target.value }))} value={form.paymentId} /></Field>
                    <Field label="Document ID"><Input onChange={(event) => setForm((current) => ({ ...current, documentId: event.target.value }))} value={form.documentId} /></Field>
                    <Field label="Amount"><Input onChange={(event) => setForm((current) => ({ ...current, amount: event.target.value }))} type="number" value={form.amount} /></Field>
                </div>
                <div className="mt-4 flex flex-wrap justify-end gap-2">
                    <Button onClick={() => setPreview(vehicleRentalApi.payments.previewAllocation(form).data)} type="button" variant="secondary">Preview Allocation Contract</Button>
                    <Button disabled={saving} type="submit" variant="blue">{saving ? 'Allocating...' : 'Allocate Payment'}</Button>
                </div>
            </FormSection>
            {preview ? <RentalPaymentPanel rows={[preview]} /> : null}
        </form>
    );
}

export function ReplacementVehiclePanel({ rows }: { rows: VehicleRentalReplacement[] }) {
    return <DataTable columns={[{ header: 'Replacement', key: 'replacementNumber' }, { header: 'Agreement', key: 'agreementNumber' }, { header: 'Original', key: 'originalVehicle' }, { header: 'Replacement vehicle', key: 'replacementVehicle' }, { header: 'Reason', key: 'reason' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function BreakdownPanel({ rows }: { rows: VehicleRentalBreakdown[] }) {
    return <DataTable columns={[{ header: 'Breakdown', key: 'breakdownNumber' }, { header: 'Agreement', key: 'agreementNumber' }, { header: 'Vehicle', key: 'vehicle' }, { header: 'Resolution', key: 'resolution' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function VehicleRentalFinancePostingPanel({ agreement }: { agreement: VehicleRentalAgreement }) {
    return <PreviewPanel rows={[{ label: 'AR impact', value: agreement.financePreview.calculated.arImpact }, { label: 'AP impact', value: agreement.financePreview.calculated.apImpact }, { label: 'Eligibility', value: agreement.financePreview.calculated.eligibility }, { label: 'Journal status', value: agreement.financePreview.calculated.journalStatus }]} status="Finance" title="Finance AR/AP Posting Preview" />;
}

export function VehicleRentalWorkflowActions({ agreement, onChanged }: { agreement: VehicleRentalAgreement; onChanged?: () => void }) {
    const [working, setWorking] = useState<string>();
    const [error, setError] = useState<string>();

    async function run(action: string, callback: () => Promise<unknown>): Promise<void> {
        setWorking(action);
        setError(undefined);
        try {
            await callback();
            onChanged?.();
        } catch (caught) {
            setError(asErrorMessage(caught));
        } finally {
            setWorking(undefined);
        }
    }

    return (
        <Card className="p-5">
            <div className="mb-4">
                <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">Workflow Actions</h3>
                <p className="mt-1 text-sm text-slate-500">Actions call backend workflow endpoints. No frontend workflow status calculation is performed.</p>
            </div>
            <FormError error={error} />
            <div className="mt-3 flex flex-wrap gap-2">
                <Link to="/vehicle-rental/availability"><Button disabled={Boolean(working)} variant="secondary">Check Availability</Button></Link>
                <Button disabled={Boolean(working)} onClick={() => run('billing', () => vehicleRentalApi.agreements.previewBilling(agreement.id))} variant="secondary">Preview Billing</Button>
                <Link to="/vehicle-rental/invoices/new"><Button disabled={Boolean(working)} variant="secondary">Generate Invoice</Button></Link>
                <Button disabled={Boolean(working)} onClick={() => run('provider-payable', () => vehicleRentalApi.providerPayables.create(agreement.id))} variant="secondary">Create Provider Payable</Button>
                <Button disabled={Boolean(working)} onClick={() => run('finance', () => vehicleRentalApi.finance.post('agreement', agreement.id))} variant="secondary">Post Finance</Button>
                <Button disabled={Boolean(working)} onClick={() => run('cancel', () => vehicleRentalApi.agreements.transition(agreement.id, 'cancelled'))} variant="danger">Cancel Agreement</Button>
            </div>
            <p className="mt-4 text-sm font-semibold text-slate-600">Current backend status: {agreement.workflowStatus}</p>
        </Card>
    );
}

export function VehicleRentalActivityTimeline({ rows }: { rows: VehicleRentalAuditEntry[] }) {
    return <div className="space-y-3">{rows.map((entry) => <Card className="p-4" key={entry.id}><p className="font-semibold text-slate-900">{entry.note}</p><p className="mt-1 text-sm text-slate-500">{entry.actor} - {entry.timestamp}</p></Card>)}</div>;
}

export function VehicleRentalSettingsForm({ settings }: { settings: VehicleRentalSettings }) {
    const [form, setForm] = useState<Record<string, unknown>>(settings as unknown as Record<string, unknown>);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState<string>();

    useEffect(() => {
        setForm(settings as unknown as Record<string, unknown>);
    }, [settings]);

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setSaving(true);
        setMessage(undefined);
        try {
            await vehicleRentalApi.settings.update(form);
            setMessage('Settings saved by backend.');
        } catch (caught) {
            setMessage(asErrorMessage(caught));
        } finally {
            setSaving(false);
        }
    }

    return (
        <form className="space-y-4" onSubmit={submit}>
            {message ? <Card className="p-4 text-sm font-semibold text-slate-700">{message}</Card> : null}
            <FormSection description="Module settings for rental sequences, document definitions, provider flow, and default financial mappings." title="Rental Settings">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {Object.entries(form).filter(([key]) => !['id', 'tenant_id', 'organization_unit_id', 'created_at', 'updated_at'].includes(key)).map(([key, value]) => (
                        <Field key={key} label={key.replaceAll('_', ' ')}>
                            <Input onChange={(event) => setForm((current) => ({ ...current, [key]: event.target.value }))} value={String(value ?? '')} />
                        </Field>
                    ))}
                </div>
                <div className="mt-4 flex justify-end">
                    <Button disabled={saving} type="submit" variant="blue">{saving ? 'Saving...' : 'Save Settings'}</Button>
                </div>
            </FormSection>
        </form>
    );
}
