import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from 'react';
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
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type {
    VehicleServiceAuditEntry,
    VehicleServiceDashboardMetric,
    VehicleServiceDiagnostic,
    VehicleServiceInspection,
    VehicleServiceInvoice,
    VehicleServiceJobCard,
    VehicleServiceJobCardFormInput,
    VehicleServiceJobCardLine,
    VehicleServiceJobCardLineFormInput,
    VehicleServiceLineType,
    VehicleServiceLookupOption,
    VehicleServicePayment,
    VehicleServicePaymentFormInput,
    VehicleServiceSettings,
    VehicleServiceType,
} from '../types/vehicleService.types';

function labelFor(options: VehicleServiceLookupOption[], id?: string): string | undefined {
    return options.find((option) => option.id === id)?.label;
}

function optionList(options: VehicleServiceLookupOption[]) {
    return options.map((option) => ({ label: option.secondary ? `${option.label} (${option.secondary})` : option.label, value: option.id }));
}

function emptyLine(lineType: VehicleServiceLineType): VehicleServiceJobCardLineFormInput {
    return {
        description: '',
        itemId: '',
        lineType,
        quantity: '1',
        requiresStockMovement: lineType === 'spare_part',
        unitPrice: '0',
        uomId: '',
    };
}

function emptyForm(): VehicleServiceJobCardFormInput {
    return {
        billingCustomerId: '',
        billingCustomerType: 'customer',
        customerComplaint: '',
        expectedCompletion: '',
        initialDiagnosis: '',
        jobCardNumber: '',
        laborItems: [],
        lines: [emptyLine('spare_part')],
        nextServiceDate: '',
        nonInventoryItems: [],
        notes: '',
        odometer: '',
        openedAt: '',
        payerId: '',
        payerType: 'customer',
        priority: 'medium',
        receivedAt: '',
        serviceCustomerId: '',
        serviceCustomerType: 'customer',
        serviceTypeId: '',
        status: 'open',
        supervisorId: '',
        vehicleId: '',
        warehouseId: '',
    };
}

function formFromJobCard(jobCard: VehicleServiceJobCard): VehicleServiceJobCardFormInput {
    return {
        ...emptyForm(),
        billingCustomerId: jobCard.partyContext.billingCustomer.id ?? '',
        billingCustomerName: jobCard.partyContext.billingCustomer.name,
        billingCustomerType: jobCard.partyContext.billingCustomer.type,
        customerComplaint: jobCard.customerComplaint,
        expectedCompletion: jobCard.expectedCompletion,
        initialDiagnosis: jobCard.initialDiagnosis,
        jobCardNumber: jobCard.jobCardNumber,
        lines: jobCard.lines
            .filter((line) => line.lineType !== 'labour' && line.lineType !== 'non_inventory')
            .map(lineToInput),
        laborItems: jobCard.lines.filter((line) => line.lineType === 'labour').map(lineToInput),
        nextServiceDate: jobCard.nextServiceDate,
        odometer: jobCard.odometer,
        openedAt: jobCard.openedAt,
        payerId: jobCard.partyContext.payer.id ?? '',
        payerName: jobCard.partyContext.payer.name,
        payerType: jobCard.partyContext.payer.type,
        serviceCustomerId: jobCard.partyContext.serviceCustomer.id ?? '',
        serviceCustomerName: jobCard.partyContext.serviceCustomer.name,
        serviceCustomerType: jobCard.partyContext.serviceCustomer.type,
        status: jobCard.status,
    };
}

function lineToInput(line: VehicleServiceJobCardLine): VehicleServiceJobCardLineFormInput {
    return {
        description: line.description,
        id: line.id,
        itemId: line.itemId ?? '',
        lineType: line.lineType,
        quantity: line.quantity,
        requiresStockMovement: line.lineType === 'spare_part',
        unitPrice: line.unitPrice ?? '0',
        uomId: line.uomId ?? '',
    };
}

function Field({
    children,
    error,
    label,
}: {
    children: ReactNode;
    error?: string[];
    label: string;
}) {
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

function asErrorMessage(error: unknown): string {
    if (error instanceof ApiError) {
        return error.message;
    }

    if (error instanceof Error) {
        return error.message;
    }

    return 'Vehicle Service request failed.';
}

function fieldErrors(error: unknown): Record<string, string[]> {
    return error instanceof ApiError ? error.errors : {};
}

const lineTypeLabels: Record<VehicleServiceLineType, string> = {
    combo: 'Combo / Package',
    customer_supplied: 'Customer-Supplied',
    external_service: 'External Service',
    labour: 'Labour',
    non_inventory: 'Non-Inventory',
    service: 'Service',
    spare_part: 'Spare Part',
};

export function VehicleServicePageHeader({ actions, subtitle, title }: { actions?: ReactNode; subtitle?: string; title: string }) {
    return <PageHeader actions={actions} eyebrow="Vehicle Service" subtitle={subtitle} title={title} />;
}

export function VehicleServiceDashboardCards({ metrics }: { metrics: VehicleServiceDashboardMetric[] }) {
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

export function ServiceTypeForm({ onSaved }: { onSaved?: () => void }) {
    const [form, setForm] = useState({ code: '', description: '', name: '', status: 'active' });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string>();

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setSaving(true);
        setError(undefined);

        try {
            await vehicleServiceApi.serviceTypes.create(form);
            setForm({ code: '', description: '', name: '', status: 'active' });
            onSaved?.();
        } catch (caught) {
            setError(asErrorMessage(caught));
        } finally {
            setSaving(false);
        }
    }

    return (
        <form className="space-y-4" onSubmit={submit}>
            <FormError error={error} />
            <FormSection description="Service type setup guides workflow defaults only. Backend owns status and validation." title="Service type">
                <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Code"><Input onChange={(event) => setForm((current) => ({ ...current, code: event.target.value }))} required value={form.code} /></Field>
                    <Field label="Name"><Input onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))} required value={form.name} /></Field>
                    <Field label="Status"><Select onChange={(event) => setForm((current) => ({ ...current, status: event.target.value }))} options={[{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }]} value={form.status} /></Field>
                    <Field label="Description"><Textarea onChange={(event) => setForm((current) => ({ ...current, description: event.target.value }))} value={form.description} /></Field>
                </div>
                <div className="mt-4 flex justify-end">
                    <Button disabled={saving} type="submit">{saving ? 'Saving...' : 'Save Service Type'}</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function ServiceTypeTable({ rows }: { rows: VehicleServiceType[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Code', key: 'code' },
                { header: 'Name', key: 'name' },
                { header: 'Description', key: 'description' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Updated', key: 'updatedAt' },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function JobCardTable({ rows }: { rows: VehicleServiceJobCard[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Job card', key: 'jobCardNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-service/job-cards/${row.id}`}>{row.jobCardNumber}</Link> },
                { header: 'Service customer', key: 'serviceCustomer', render: (row) => row.partyContext.serviceCustomer.name },
                { header: 'Billing party', key: 'billingCustomer', render: (row) => row.partyContext.billingCustomer.name },
                { header: 'Vehicle', key: 'vehicle' },
                { header: 'Service type', key: 'serviceType' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Inventory', key: 'inventoryStatus', render: (row) => row.stockPreview.calculated.stockEffect },
                { header: 'Updated', key: 'updatedAt' },
                { header: 'Actions', key: 'actions', render: (row) => <Link to={`/vehicle-service/job-cards/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

function LineEditor({
    items,
    line,
    onChange,
    warehouses,
}: {
    items: VehicleServiceLookupOption[];
    line: VehicleServiceJobCardLineFormInput;
    onChange: (line: VehicleServiceJobCardLineFormInput) => void;
    warehouses: VehicleServiceLookupOption[];
}) {
    const [uoms, setUoms] = useState<VehicleServiceLookupOption[]>([]);

    useEffect(() => {
        if (!line.itemId) {
            setUoms([]);
            return;
        }

        vehicleServiceApi.lookups.itemUnits(line.itemId)
            .then((response) => {
                setUoms(response.data);
                if (line.uomId && !response.data.some((unit) => unit.id === line.uomId)) {
                    onChange({ ...line, uomId: response.data[0]?.id ?? '' });
                } else if (!line.uomId && response.data[0]) {
                    onChange({ ...line, uomId: response.data[0].id });
                }
            })
            .catch(() => setUoms([]));
    }, [line.itemId]);

    return (
        <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 md:grid-cols-[150px_1fr_100px_130px_120px_1fr]">
            <Select onChange={(event) => onChange({ ...line, lineType: event.target.value as VehicleServiceLineType, requiresStockMovement: event.target.value === 'spare_part' })} options={Object.entries(lineTypeLabels).map(([value, label]) => ({ label, value }))} value={line.lineType} />
            <Select onChange={(event) => onChange({ ...line, itemId: event.target.value, uomId: '' })} options={optionList(items)} placeholder="Select item/service" value={line.itemId} />
            <Input min="0.0001" onChange={(event) => onChange({ ...line, quantity: event.target.value })} type="number" value={line.quantity} />
            <Select disabled={!line.itemId} onChange={(event) => onChange({ ...line, uomId: event.target.value })} options={optionList(uoms)} placeholder={line.itemId ? 'Item UOM' : 'Select item first'} value={line.uomId} />
            <Input min="0" onChange={(event) => onChange({ ...line, unitPrice: event.target.value })} type="number" value={line.unitPrice} />
            <Select onChange={(event) => onChange({ ...line, warehouseId: event.target.value })} options={optionList(warehouses)} placeholder="Warehouse" value={line.warehouseId ?? ''} />
            <div className="md:col-span-6"><Textarea onChange={(event) => onChange({ ...line, description: event.target.value })} placeholder="Line notes" value={line.description} /></div>
        </div>
    );
}

function useLookups() {
    const [customers, setCustomers] = useState<VehicleServiceLookupOption[]>([]);
    const [employees, setEmployees] = useState<VehicleServiceLookupOption[]>([]);
    const [items, setItems] = useState<VehicleServiceLookupOption[]>([]);
    const [serviceTypes, setServiceTypes] = useState<VehicleServiceLookupOption[]>([]);
    const [vehicles, setVehicles] = useState<VehicleServiceLookupOption[]>([]);
    const [warehouses, setWarehouses] = useState<VehicleServiceLookupOption[]>([]);

    useEffect(() => {
        Promise.all([
            vehicleServiceApi.lookups.customers(),
            vehicleServiceApi.lookups.employees(),
            vehicleServiceApi.lookups.items(),
            vehicleServiceApi.serviceTypes.list(),
            vehicleServiceApi.lookups.vehicles(),
            vehicleServiceApi.lookups.warehouses(),
        ]).then(([customerResponse, employeeResponse, itemResponse, serviceTypeResponse, vehicleResponse, warehouseResponse]) => {
            setCustomers(customerResponse.data);
            setEmployees(employeeResponse.data);
            setItems(itemResponse.data);
            setServiceTypes(serviceTypeResponse.data.map((serviceType) => ({ id: serviceType.id, label: `${serviceType.code} - ${serviceType.name}` })));
            setVehicles(vehicleResponse.data);
            setWarehouses(warehouseResponse.data);
        }).catch(() => undefined);
    }, []);

    return { customers, employees, items, serviceTypes, vehicles, warehouses };
}

export function JobCardForm({ jobCard, mode = 'create' }: { jobCard?: VehicleServiceJobCard; mode?: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const lookups = useLookups();
    const [form, setForm] = useState<VehicleServiceJobCardFormInput>(() => jobCard ? formFromJobCard(jobCard) : emptyForm());
    const [activeTab, setActiveTab] = useState('intake');
    const [error, setError] = useState<string>();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (jobCard) {
            setForm(formFromJobCard(jobCard));
        }
    }, [jobCard?.id]);

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setSaving(true);
        setError(undefined);
        setErrors({});

        try {
            const response = mode === 'edit' && jobCard
                ? await vehicleServiceApi.jobCards.update(jobCard.id, form)
                : await vehicleServiceApi.jobCards.create(form);
            navigate(`/vehicle-service/job-cards/${response.data.id}`);
        } catch (caught) {
            setError(asErrorMessage(caught));
            setErrors(fieldErrors(caught));
        } finally {
            setSaving(false);
        }
    }

    function updateLine(group: 'lines' | 'laborItems' | 'nonInventoryItems', index: number, line: VehicleServiceJobCardLineFormInput): void {
        setForm((current) => ({
            ...current,
            [group]: current[group].map((existing, currentIndex) => currentIndex === index ? line : existing),
        }));
    }

    function addLine(group: 'lines' | 'laborItems' | 'nonInventoryItems', lineType: VehicleServiceLineType): void {
        setForm((current) => ({ ...current, [group]: [...current[group], emptyLine(lineType)] }));
    }

    const tabs = [
        { label: 'Header / Vehicle & Customer', value: 'intake' },
        { label: 'Service & Parts', value: 'lines' },
        { label: 'Labour / Technicians', value: 'labour' },
        { label: 'Review', value: 'review' },
    ];

    return (
        <form className="space-y-5" onSubmit={submit}>
            <FormError error={error} />
            <Card className="p-5">
                <Tabs active={activeTab} items={tabs} onChange={setActiveTab} trailing={<StatusBadge status="Real backend" />} />
            </Card>

            {activeTab === 'intake' ? (
                <FormSection description="Customer, vehicle, party roles, sequence and tenant rules are validated by backend." title="Intake & Header">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field error={errors.job_card_number} label="Job card number"><Input onChange={(event) => setForm((current) => ({ ...current, jobCardNumber: event.target.value }))} placeholder="Leave blank to generate temporary draft number" value={form.jobCardNumber} /></Field>
                        <Field error={errors.service_type_id} label="Service type"><Select onChange={(event) => setForm((current) => ({ ...current, serviceTypeId: event.target.value }))} options={optionList(lookups.serviceTypes)} placeholder="Select type" value={form.serviceTypeId} /></Field>
                        <Field error={errors.service_customer_id ?? errors.linked_customer_id} label="Service customer"><Select onChange={(event) => setForm((current) => ({ ...current, serviceCustomerId: event.target.value, billingCustomerId: current.billingCustomerId || event.target.value, payerId: current.payerId || event.target.value }))} options={optionList(lookups.customers)} placeholder="Select service customer" value={form.serviceCustomerId} /></Field>
                        <Field error={errors.billing_customer_id} label="Billing customer"><Select onChange={(event) => setForm((current) => ({ ...current, billingCustomerId: event.target.value }))} options={optionList(lookups.customers)} placeholder="May differ from service customer" value={form.billingCustomerId} /></Field>
                        <Field error={errors.payer_id} label="Payer"><Select onChange={(event) => setForm((current) => ({ ...current, payerId: event.target.value }))} options={optionList(lookups.customers)} placeholder="May differ from billing customer" value={form.payerId} /></Field>
                        <Field error={errors.vehicle_id} label="Vehicle"><Select onChange={(event) => setForm((current) => ({ ...current, vehicleId: event.target.value }))} options={optionList(lookups.vehicles)} placeholder="Select vehicle" value={form.vehicleId} /></Field>
                        <Field error={errors.assigned_to} label="Supervisor"><Select onChange={(event) => setForm((current) => ({ ...current, supervisorId: event.target.value }))} options={optionList(lookups.employees)} placeholder="Select supervisor" value={form.supervisorId} /></Field>
                        <Field error={errors.warehouse_id} label="Default warehouse"><Select onChange={(event) => setForm((current) => ({ ...current, warehouseId: event.target.value }))} options={optionList(lookups.warehouses)} placeholder="Select warehouse" value={form.warehouseId} /></Field>
                        <Field label="Opened date"><Input onChange={(event) => setForm((current) => ({ ...current, openedAt: event.target.value }))} type="datetime-local" value={form.openedAt} /></Field>
                        <Field label="Expected completion"><Input onChange={(event) => setForm((current) => ({ ...current, expectedCompletion: event.target.value }))} type="datetime-local" value={form.expectedCompletion} /></Field>
                        <Field label="Odometer"><Input onChange={(event) => setForm((current) => ({ ...current, odometer: event.target.value }))} type="number" value={form.odometer} /></Field>
                        <Field label="Next service date"><Input onChange={(event) => setForm((current) => ({ ...current, nextServiceDate: event.target.value }))} type="date" value={form.nextServiceDate} /></Field>
                        <div className="md:col-span-2"><Field label="Customer complaint"><Textarea onChange={(event) => setForm((current) => ({ ...current, customerComplaint: event.target.value }))} value={form.customerComplaint} /></Field></div>
                        <div className="md:col-span-2"><Field label="Initial diagnosis"><Textarea onChange={(event) => setForm((current) => ({ ...current, initialDiagnosis: event.target.value }))} value={form.initialDiagnosis} /></Field></div>
                    </div>
                </FormSection>
            ) : null}

            {activeTab === 'lines' ? (
                <FormSection description="Frontend collects item, quantity, UOM and rate. Backend owns price/tax/discount/UOM/stock effects." title="Service, Parts and Non-Inventory Lines">
                    <div className="space-y-3">
                        {form.lines.map((line, index) => <LineEditor items={lookups.items} key={`line-${index}`} line={line} onChange={(next) => updateLine('lines', index, next)} warehouses={lookups.warehouses} />)}
                        {form.nonInventoryItems.map((line, index) => <LineEditor items={lookups.items} key={`non-${index}`} line={line} onChange={(next) => updateLine('nonInventoryItems', index, next)} warehouses={lookups.warehouses} />)}
                        <div className="flex flex-wrap gap-2">
                            <Button onClick={() => addLine('lines', 'service')} type="button" variant="secondary">Add Service</Button>
                            <Button onClick={() => addLine('lines', 'spare_part')} type="button" variant="secondary">Add Part</Button>
                            <Button onClick={() => addLine('nonInventoryItems', 'non_inventory')} type="button" variant="secondary">Add Non-Inventory</Button>
                        </div>
                    </div>
                </FormSection>
            ) : null}

            {activeTab === 'labour' ? (
                <FormSection description="Labour items are persisted as backend labour rows. Technician assignment is managed from the job detail page." title="Labour Items">
                    <div className="space-y-3">
                        {form.laborItems.map((line, index) => <LineEditor items={lookups.items} key={`labor-${index}`} line={line} onChange={(next) => updateLine('laborItems', index, { ...next, lineType: 'labour', requiresStockMovement: false })} warehouses={lookups.warehouses} />)}
                        <Button onClick={() => addLine('laborItems', 'labour')} type="button" variant="secondary">Add Labour</Button>
                    </div>
                </FormSection>
            ) : null}

            {activeTab === 'review' ? (
                <div className="grid gap-5 xl:grid-cols-3">
                    <PreviewPanel rows={[
                        { label: 'Service customer', value: labelFor(lookups.customers, form.serviceCustomerId) ?? 'Not selected' },
                        { label: 'Billing customer', value: labelFor(lookups.customers, form.billingCustomerId) ?? 'Not selected' },
                        { label: 'Vehicle', value: labelFor(lookups.vehicles, form.vehicleId) ?? 'Not selected' },
                        { label: 'Lines', value: String(form.lines.length + form.laborItems.length + form.nonInventoryItems.length) },
                    ]} status="Backend save" title="Draft Summary" />
                    <PreviewPanel rows={[
                        { label: 'Stock preview', value: 'Use detail workflow after saving; backend posts stock effects.' },
                        { label: 'Invoice preview', value: 'Backend preview available after job card is saved.' },
                    ]} status="Backend-owned" title="Preview Contracts" />
                </div>
            ) : null}

            <div className="flex justify-end gap-2">
                <Link to="/vehicle-service/job-cards"><Button type="button" variant="secondary">Cancel</Button></Link>
                <Button disabled={saving} type="submit" variant="blue">{saving ? 'Saving...' : mode === 'edit' ? 'Save Job Card' : 'Create Job Card'}</Button>
            </div>
        </form>
    );
}

export function VehicleServicePartyContextPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return <PreviewPanel rows={[
        { label: 'Vehicle owner', value: `${jobCard.partyContext.vehicleOwner.owner.name} (${jobCard.partyContext.vehicleOwner.owner.type})` },
        { label: 'Ownership', value: `${jobCard.partyContext.vehicleOwner.ownershipType} / ${jobCard.partyContext.vehicleOwner.ownershipRole}` },
        { label: 'Service customer', value: `${jobCard.partyContext.serviceCustomer.name} (${jobCard.partyContext.serviceCustomer.type})` },
        { label: 'Billing customer', value: `${jobCard.partyContext.billingCustomer.name} (${jobCard.partyContext.billingCustomer.type})` },
        { label: 'Payer', value: `${jobCard.partyContext.payer.name} (${jobCard.partyContext.payer.type})` },
        { label: 'Mismatch notice', value: jobCard.partyContext.mismatchNotice || 'No warning returned' },
    ]} status="Party Context" title="Owner / Billing / Payer Context" />;
}

export function JobCardLineTable({ rows }: { rows: VehicleServiceJobCardLine[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Type', key: 'lineType', render: (row) => <StatusBadge status={lineTypeLabels[row.lineType]} /> },
                { header: 'Item / Service', key: 'item' },
                { header: 'Description', key: 'description' },
                { header: 'Qty', key: 'quantity' },
                { header: 'UOM', key: 'uom' },
                { header: 'Stock behavior', key: 'stockBehavior' },
                { header: 'Unit price', key: 'unitPrice' },
                { header: 'Backend amount', key: 'backendCalculatedAmount' },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

function LineSection({ rows, title }: { rows: VehicleServiceJobCardLine[]; title: string }) {
    return <Card className="p-5"><h3 className="mb-4 text-base font-bold text-slate-950">{title}</h3><JobCardLineTable rows={rows} /></Card>;
}

export function SparePartsSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection rows={lines.filter((line) => line.lineType === 'spare_part')} title="Spare Parts / Stock Items" />;
}

export function NonInventoryItemsSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection rows={lines.filter((line) => line.lineType === 'non_inventory')} title="Non-Inventory Items" />;
}

export function CustomerSuppliedItemsSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection rows={lines.filter((line) => line.lineType === 'customer_supplied')} title="Customer-Supplied Items" />;
}

export function ExternalServicesSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection rows={lines.filter((line) => line.lineType === 'external_service')} title="External Services" />;
}

export function LabourAssignmentPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    const [rows, setRows] = useState(jobCard.labourAssignments);
    useEffect(() => {
        vehicleServiceApi.labour.listAssignments(jobCard.id).then((response) => setRows(response.data)).catch(() => undefined);
    }, [jobCard.id]);

    return (
        <DataTable
            columns={[
                { header: 'Labour item', key: 'labourItem' },
                { header: 'Employee', key: 'employee' },
                { header: 'Assignment', key: 'assignmentType' },
                { header: 'Backend incentive/share', key: 'incentivePreview' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function StockAvailabilityPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return <PreviewPanel rows={[
        { label: 'Requested quantity', value: jobCard.stockPreview.calculated.requestedQuantity },
        { label: 'Reserved quantity', value: jobCard.stockPreview.calculated.reservedQuantity },
        { label: 'Decision', value: jobCard.stockPreview.calculated.availabilityDecision },
        { label: 'Stock effect', value: jobCard.stockPreview.calculated.stockEffect },
    ]} status="Backend" title="Stock Availability" />;
}

export function ServiceInvoicePreviewPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return <PreviewPanel rows={[
        { label: 'Subtotal', value: jobCard.invoicePreview.calculated.subtotal },
        { label: 'Discount', value: jobCard.invoicePreview.calculated.discountTotal },
        { label: 'Tax', value: jobCard.invoicePreview.calculated.taxTotal },
        { label: 'Grand total', value: jobCard.invoicePreview.calculated.grandTotal },
    ]} status="Backend" title="Service Invoice Preview" />;
}

export function ServiceInvoiceDocumentPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return <PreviewPanel rows={[
        { label: 'Document number', value: jobCard.documentPreview.documentNumber },
        { label: 'Template', value: jobCard.documentPreview.template },
        { label: 'Status', value: jobCard.documentPreview.status },
    ]} status="Document" title="Document Integration" />;
}

export function VehicleServiceFinancePostingPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return <PreviewPanel rows={[
        { label: 'AR impact', value: jobCard.financePreview.calculated.arImpact },
        { label: 'Eligibility', value: jobCard.financePreview.calculated.eligibility },
        { label: 'Journal status', value: jobCard.financePreview.calculated.journalStatus },
    ]} status="Finance" title="Finance / AR Posting" />;
}

export function ServicePaymentPanel({ payments }: { payments: VehicleServicePayment[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Payment', key: 'paymentNumber' },
                { header: 'Payer', key: 'payer' },
                { header: 'Method', key: 'method' },
                { header: 'Amount', key: 'amount' },
                { header: 'Source invoice/job', key: 'sourceInvoice' },
                { header: 'Backend allocation', key: 'allocationPreview' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={payments}
        />
    );
}

export function DiagnosticsPanel({ rows }: { rows: VehicleServiceDiagnostic[] }) {
    return <DataTable columns={[{ header: 'Diagnostic', key: 'diagnosticNumber' }, { header: 'Phase', key: 'phase' }, { header: 'Findings', key: 'findings' }, { header: 'Recommendation', key: 'recommendation' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function InspectionPanel({ rows }: { rows: VehicleServiceInspection[] }) {
    return <DataTable columns={[{ header: 'Inspection', key: 'inspectionNumber' }, { header: 'Phase', key: 'phase' }, { header: 'Result', key: 'result' }, { header: 'Notes', key: 'notes' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function VehicleServiceWorkflowActions({ jobCard, onChanged }: { jobCard: VehicleServiceJobCard; onChanged?: () => void }) {
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
                <p className="mt-1 text-sm text-slate-500">Actions call backend workflow endpoints. Unsupported transitions return backend errors.</p>
            </div>
            <FormError error={error} />
            <div className="mt-3 flex flex-wrap gap-2">
                <Link to="/vehicle-service/invoices/new"><Button disabled={Boolean(working)} variant="secondary">Generate Invoice</Button></Link>
                <Button disabled={Boolean(working)} onClick={() => run('inventory', () => vehicleServiceApi.stock.postConsumption(jobCard.id))} variant="secondary">Post Inventory</Button>
                <Button disabled={Boolean(working)} onClick={() => run('finance', () => vehicleServiceApi.finance.post(jobCard.id))} variant="secondary">Post Finance</Button>
                <Button disabled={Boolean(working)} onClick={() => run('cancel', () => vehicleServiceApi.jobCards.transition(jobCard.id, 'cancelled'))} variant="danger">Cancel Job</Button>
            </div>
            <p className="mt-4 text-sm font-semibold text-slate-600">Current backend status: {jobCard.workflowStatus}</p>
        </Card>
    );
}

export function VehicleServiceActivityTimeline({ rows }: { rows: VehicleServiceAuditEntry[] }) {
    return <div className="space-y-3">{rows.map((entry) => <Card className="p-4" key={`vehicle-service-activity-${entry.id}-${entry.timestamp}`}><p className="font-semibold text-slate-900">{entry.note}</p><p className="mt-1 text-sm text-slate-500">{entry.actor} · {entry.timestamp}</p></Card>)}</div>;
}

export function VehicleServiceSettingsForm({ settings }: { settings: VehicleServiceSettings }) {
    const [form, setForm] = useState<Record<string, unknown>>(settings as unknown as Record<string, unknown>);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState<string>();

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setSaving(true);
        setMessage(undefined);
        try {
            await vehicleServiceApi.settings.update(form);
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
            <FormSection description="Vehicle Service settings remain module-specific. Global configuration stays outside this module." title="Workshop settings">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {Object.entries(form).filter(([key]) => !['id', 'tenant_id', 'organization_unit_id', 'created_at', 'updated_at'].includes(key)).map(([key, value]) => (
                        <Field key={key} label={key.replaceAll('_', ' ')}>
                            <Input onChange={(event) => setForm((current) => ({ ...current, [key]: event.target.value }))} value={String(value ?? '')} />
                        </Field>
                    ))}
                </div>
                <div className="mt-4 flex justify-end">
                    <Button disabled={saving} type="submit">{saving ? 'Saving...' : 'Save Settings'}</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function ServiceInvoiceTable({ rows }: { rows: VehicleServiceInvoice[] }) {
    return <DataTable columns={[{ header: 'Invoice', key: 'invoiceNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-service/invoices/${row.id}`}>{row.invoiceNumber}</Link> }, { header: 'Job card', key: 'jobCardNumber' }, { header: 'Billing customer', key: 'billingCustomer' }, { header: 'Backend total', key: 'previewTotal' }, { header: 'Document', key: 'documentStatus' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }, { header: 'Updated', key: 'updatedAt' }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function PaymentCreateForm() {
    const navigate = useNavigate();
    const [form, setForm] = useState<VehicleServicePaymentFormInput>({ amount: '', documentId: '', documentType: 'document', jobCardId: '', paymentId: '' });
    const [jobs, setJobs] = useState<VehicleServiceJobCard[]>([]);
    const [preview, setPreview] = useState<VehicleServicePayment>();
    const [error, setError] = useState<string>();
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        vehicleServiceApi.jobCards.list().then((response) => setJobs(response.data)).catch(() => undefined);
    }, []);

    function update<K extends keyof VehicleServicePaymentFormInput>(key: K, value: VehicleServicePaymentFormInput[K]): void {
        setForm((current) => ({ ...current, [key]: value }));
    }

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setSaving(true);
        setError(undefined);

        try {
            await vehicleServiceApi.payments.create(form);
            navigate('/vehicle-service/payments');
        } catch (caught) {
            setError(asErrorMessage(caught));
        } finally {
            setSaving(false);
        }
    }

    const jobOptions = jobs.map((job) => ({ label: `${job.jobCardNumber} - ${job.partyContext.billingCustomer.name}`, value: job.id }));

    return (
        <form className="space-y-5" onSubmit={submit}>
            <FormError error={error} />
            <FormSection description="Allocates an existing backend Payment to a service job/document. Backend owns balances and AR posting." title="Service payment allocation">
                <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Job card"><Select onChange={(event) => update('jobCardId', event.target.value)} options={jobOptions} value={form.jobCardId} /></Field>
                    <Field label="Existing payment ID"><Input onChange={(event) => update('paymentId', event.target.value)} value={form.paymentId} /></Field>
                    <Field label="Document ID"><Input onChange={(event) => update('documentId', event.target.value)} value={form.documentId} /></Field>
                    <Field label="Amount"><Input onChange={(event) => update('amount', event.target.value)} type="number" value={form.amount} /></Field>
                </div>
                <div className="mt-4 flex flex-wrap justify-end gap-2">
                    <Button onClick={() => setPreview(vehicleServiceApi.payments.previewAllocation(form).data)} type="button" variant="secondary">Preview Allocation Contract</Button>
                    <Button disabled={saving} type="submit" variant="blue">{saving ? 'Allocating...' : 'Allocate Payment'}</Button>
                </div>
            </FormSection>
            {preview ? <ServicePaymentPanel payments={[preview]} /> : null}
        </form>
    );
}
