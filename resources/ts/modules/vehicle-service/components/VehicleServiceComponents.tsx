import { useCallback, useEffect, useMemo, useRef, useState, type FormEvent, type ReactNode } from 'react';
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
    VehicleServiceLabourAssignmentFormInput,
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

function clientKey(prefix: string): string {
    return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

function emptyLine(lineType: VehicleServiceLineType): VehicleServiceJobCardLineFormInput {
    return {
        clientKey: clientKey(lineType),
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
        labourAssignments: [],
        laborItems: [],
        lines: [],
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
        labourAssignments: jobCard.labourAssignments.map((assignment) => ({
            clientKey: assignment.id,
            employeeId: '',
            hoursWorked: '',
            laborItemId: '',
            notes: '',
            quantity: '1',
            role: assignment.assignmentType,
            status: assignment.status,
        })),
        nextServiceDate: jobCard.nextServiceDate,
        nonInventoryItems: jobCard.lines.filter((line) => line.lineType === 'non_inventory').map(lineToInput),
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
        clientKey: line.id || clientKey(line.lineType),
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
    allowedLineTypes,
    itemContext,
    items,
    line,
    loadItems,
    loadWarehouses,
    onChange,
    warehouses,
}: {
    allowedLineTypes: VehicleServiceLineType[];
    itemContext: 'service-lines' | 'non-inventory' | 'labour';
    items: VehicleServiceLookupOption[];
    line: VehicleServiceJobCardLineFormInput;
    loadItems: (context: 'service-lines' | 'non-inventory' | 'labour', search?: string) => void;
    loadWarehouses: () => void;
    onChange: (line: VehicleServiceJobCardLineFormInput) => void;
    warehouses: VehicleServiceLookupOption[];
}) {
    const [uoms, setUoms] = useState<VehicleServiceLookupOption[]>([]);
    const [comboComponents, setComboComponents] = useState<VehicleServiceLookupOption[]>([]);
    const [itemSearch, setItemSearch] = useState('');

    useEffect(() => {
        if (!line.itemId) {
            setUoms([]);
            return;
        }

        let active = true;
        vehicleServiceApi.lookups.itemUnits(line.itemId)
            .then((response) => {
                if (!active) return;
                setUoms(response.data);
                if (line.uomId && !response.data.some((unit) => unit.id === line.uomId)) {
                    onChange({ ...line, uomId: response.data[0]?.id ?? '' });
                } else if (!line.uomId && response.data[0]) {
                    onChange({ ...line, uomId: response.data[0].id });
                }
            })
            .catch(() => {
                if (active) setUoms([]);
            });

        return () => {
            active = false;
        };
    }, [line.itemId]);

    useEffect(() => {
        if (!line.itemId || line.lineType !== 'combo') {
            setComboComponents([]);
            return;
        }

        let active = true;
        vehicleServiceApi.lookups.comboComponents(line.itemId)
            .then((response) => {
                if (active) setComboComponents(response.data);
            })
            .catch(() => {
                if (active) setComboComponents([]);
            });

        return () => {
            active = false;
        };
    }, [line.itemId, line.lineType]);

    return (
        <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 md:grid-cols-[150px_1fr_100px_130px_120px_1fr]">
            <Select onChange={(event) => onChange({ ...line, lineType: event.target.value as VehicleServiceLineType, requiresStockMovement: event.target.value === 'spare_part' })} options={allowedLineTypes.map((value) => ({ label: lineTypeLabels[value], value }))} value={line.lineType} />
            <div className="space-y-2">
                <Input onChange={(event) => {
                    setItemSearch(event.target.value);
                    loadItems(itemContext, event.target.value);
                }} onFocus={() => loadItems(itemContext, itemSearch)} placeholder="Search item" value={itemSearch} />
                <Select onFocus={() => loadItems(itemContext, itemSearch)} onMouseDown={() => loadItems(itemContext, itemSearch)} onChange={(event) => onChange({ ...line, itemId: event.target.value, uomId: '' })} options={optionList(items)} placeholder="Select item/service" value={line.itemId} />
            </div>
            <Input min="0.0001" onChange={(event) => onChange({ ...line, quantity: event.target.value })} type="number" value={line.quantity} />
            <Select disabled={!line.itemId} onChange={(event) => onChange({ ...line, uomId: event.target.value })} options={optionList(uoms)} placeholder={line.itemId ? 'Item UOM' : 'Select item first'} value={line.uomId} />
            <Input min="0" onChange={(event) => onChange({ ...line, unitPrice: event.target.value })} type="number" value={line.unitPrice} />
            <Select onFocus={loadWarehouses} onMouseDown={loadWarehouses} onChange={(event) => onChange({ ...line, warehouseId: event.target.value })} options={optionList(warehouses)} placeholder="Warehouse" value={line.warehouseId ?? ''} />
            <div className="md:col-span-6"><Textarea onChange={(event) => onChange({ ...line, description: event.target.value })} placeholder="Line notes" value={line.description} /></div>
            {line.lineType === 'combo' && comboComponents.length ? (
                <div className="md:col-span-6 rounded-md border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900">
                    <p className="font-semibold">Backend combo components</p>
                    <div className="mt-2 grid gap-2 md:grid-cols-2">
                        {comboComponents.map((component) => (
                            <div className="rounded border border-blue-100 bg-white px-3 py-2" key={`combo-component-${line.clientKey}-${component.id}`}>
                                <span className="font-semibold">{component.label}</span>
                                {component.secondary ? <span className="ml-2 text-blue-700">{component.secondary}</span> : null}
                            </div>
                        ))}
                    </div>
                </div>
            ) : null}
        </div>
    );
}

function useLookups() {
    const [customers, setCustomers] = useState<VehicleServiceLookupOption[]>([]);
    const [employees, setEmployees] = useState<VehicleServiceLookupOption[]>([]);
    const [items, setItems] = useState<Record<'service-lines' | 'non-inventory' | 'labour', VehicleServiceLookupOption[]>>({
        labour: [],
        'non-inventory': [],
        'service-lines': [],
    });
    const [serviceTypes, setServiceTypes] = useState<VehicleServiceLookupOption[]>([]);
    const [vehicles, setVehicles] = useState<VehicleServiceLookupOption[]>([]);
    const [warehouses, setWarehouses] = useState<VehicleServiceLookupOption[]>([]);
    const mountedRef = useRef(true);
    const loadingRef = useRef(new Set<string>());

    useEffect(() => () => {
        mountedRef.current = false;
    }, []);

    const load = useCallback(async (name: 'customers' | 'employees' | 'items' | 'serviceTypes' | 'vehicles' | 'warehouses', context: 'service-lines' | 'non-inventory' | 'labour' = 'service-lines', search = '') => {
        const key = name === 'items' || name === 'employees' ? `${name}:${context}:${search.trim()}` : name;
        if (loadingRef.current.has(key)) return;

        let shouldLoad = false;
        if (name === 'items') {
            shouldLoad = search.trim() !== '' || items[context].length === 0;
        } else if (name === 'customers') {
            shouldLoad = customers.length === 0;
        } else if (name === 'employees') {
            shouldLoad = employees.length === 0;
        } else if (name === 'serviceTypes') {
            shouldLoad = serviceTypes.length === 0;
        } else if (name === 'vehicles') {
            shouldLoad = vehicles.length === 0;
        } else {
            shouldLoad = warehouses.length === 0;
        }
        if (!shouldLoad) return;

        loadingRef.current.add(key);
        try {
            if (name === 'customers') {
                const response = await vehicleServiceApi.lookups.customers();
                if (mountedRef.current) setCustomers((current) => current.length ? current : response.data);
            } else if (name === 'employees') {
                const response = await vehicleServiceApi.lookups.employees(search);
                if (mountedRef.current) setEmployees((current) => search.trim() === '' && current.length ? current : response.data);
            } else if (name === 'items') {
                const response = await vehicleServiceApi.lookups.items(context, search);
                if (mountedRef.current) setItems((current) => search.trim() === '' && current[context].length ? current : { ...current, [context]: response.data });
            } else if (name === 'serviceTypes') {
                const response = await vehicleServiceApi.serviceTypes.list({ per_page: 25 });
                if (mountedRef.current) setServiceTypes((current) => current.length ? current : response.data.map((serviceType) => ({ id: serviceType.id, label: `${serviceType.code} - ${serviceType.name}` })));
            } else if (name === 'vehicles') {
                const response = await vehicleServiceApi.lookups.vehicles();
                if (mountedRef.current) setVehicles((current) => current.length ? current : response.data);
            } else {
                const response = await vehicleServiceApi.lookups.warehouses();
                if (mountedRef.current) setWarehouses((current) => current.length ? current : response.data);
            }
        } finally {
            loadingRef.current.delete(key);
        }
    }, [customers, employees, items, serviceTypes, vehicles, warehouses]);

    return { customers, employees, items, load, serviceTypes, vehicles, warehouses };
}

export function JobCardForm({ jobCard, mode = 'create' }: { jobCard?: VehicleServiceJobCard; mode?: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const lookups = useLookups();
    const [form, setForm] = useState<VehicleServiceJobCardFormInput>(() => jobCard ? formFromJobCard(jobCard) : emptyForm());
    const [activeTab, setActiveTab] = useState('intake');
    const [error, setError] = useState<string>();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [employeeSearch, setEmployeeSearch] = useState('');
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

    function removeLine(group: 'lines' | 'laborItems' | 'nonInventoryItems', index: number): void {
        setForm((current) => ({
            ...current,
            [group]: current[group].filter((_, currentIndex) => currentIndex !== index),
        }));
    }

    function setAssignmentForLine(line: VehicleServiceJobCardLineFormInput, patch: Partial<VehicleServiceLabourAssignmentFormInput>): void {
        setForm((current) => {
            const existingIndex = current.labourAssignments.findIndex((assignment) => (
                assignment.laborItemId === line.id || assignment.laborItemClientKey === line.clientKey
            ));
            const base: VehicleServiceLabourAssignmentFormInput = existingIndex >= 0
                ? current.labourAssignments[existingIndex]
                : {
                    clientKey: clientKey('assignment'),
                    employeeId: '',
                    hoursWorked: '',
                    laborItemClientKey: line.clientKey,
                    laborItemId: line.id,
                    notes: '',
                    quantity: line.quantity || '1',
                    role: 'technician',
                    status: 'assigned',
                };
            const next = { ...base, ...patch };

            return {
                ...current,
                labourAssignments: existingIndex >= 0
                    ? current.labourAssignments.map((assignment, index) => index === existingIndex ? next : assignment)
                    : [...current.labourAssignments, next],
            };
        });
    }

    const assignmentForLine = useCallback((line: VehicleServiceJobCardLineFormInput): VehicleServiceLabourAssignmentFormInput | undefined => (
        form.labourAssignments.find((assignment) => assignment.laborItemId === line.id || assignment.laborItemClientKey === line.clientKey)
    ), [form.labourAssignments]);

    const tabs = [
        { label: 'Header Details', value: 'intake' },
        { label: 'Items / Service Lines', value: 'lines' },
        { label: 'Non-Inventory Items', value: 'non_inventory' },
        { label: 'Labour Assignments', value: 'labour' },
    ];

    return (
        <form className="space-y-5" onSubmit={submit}>
            <FormError error={error} />
            <Card className="p-5">
                <Tabs active={activeTab} items={tabs} onChange={setActiveTab} trailing={<StatusBadge status="Real backend" />} />
            </Card>

            {activeTab === 'intake' ? (
                <FormSection description="Customer, vehicle, billing party and workflow defaults are validated by backend. Leave job card number blank for backend sequence generation." title="Header Details">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field error={errors.job_card_number} label="Job card number"><Input onChange={(event) => setForm((current) => ({ ...current, jobCardNumber: event.target.value }))} placeholder="Backend generated if blank" value={form.jobCardNumber} /></Field>
                        <Field error={errors.service_type_id} label="Service type"><Select onFocus={() => void lookups.load('serviceTypes')} onMouseDown={() => void lookups.load('serviceTypes')} onChange={(event) => setForm((current) => ({ ...current, serviceTypeId: event.target.value }))} options={optionList(lookups.serviceTypes)} placeholder="Select type" value={form.serviceTypeId} /></Field>
                        <Field error={errors.service_customer_id ?? errors.linked_customer_id} label="Service customer"><Select onFocus={() => void lookups.load('customers')} onMouseDown={() => void lookups.load('customers')} onChange={(event) => setForm((current) => ({ ...current, serviceCustomerId: event.target.value, billingCustomerId: current.billingCustomerId || event.target.value, payerId: current.payerId || event.target.value }))} options={optionList(lookups.customers)} placeholder="Select service customer" value={form.serviceCustomerId} /></Field>
                        <Field error={errors.billing_customer_id} label="Billing customer"><Select onFocus={() => void lookups.load('customers')} onMouseDown={() => void lookups.load('customers')} onChange={(event) => setForm((current) => ({ ...current, billingCustomerId: event.target.value }))} options={optionList(lookups.customers)} placeholder="May differ from service customer" value={form.billingCustomerId} /></Field>
                        <Field error={errors.payer_id} label="Payer"><Select onFocus={() => void lookups.load('customers')} onMouseDown={() => void lookups.load('customers')} onChange={(event) => setForm((current) => ({ ...current, payerId: event.target.value }))} options={optionList(lookups.customers)} placeholder="May differ from billing customer" value={form.payerId} /></Field>
                        <Field error={errors.vehicle_id} label="Vehicle"><Select onFocus={() => void lookups.load('vehicles')} onMouseDown={() => void lookups.load('vehicles')} onChange={(event) => setForm((current) => ({ ...current, vehicleId: event.target.value }))} options={optionList(lookups.vehicles)} placeholder="Select vehicle" value={form.vehicleId} /></Field>
                        <Field error={errors.assigned_to} label="Supervisor"><Select onFocus={() => void lookups.load('employees')} onMouseDown={() => void lookups.load('employees')} onChange={(event) => setForm((current) => ({ ...current, supervisorId: event.target.value }))} options={optionList(lookups.employees)} placeholder="Select supervisor" value={form.supervisorId} /></Field>
                        <Field error={errors.warehouse_id} label="Default warehouse"><Select onFocus={() => void lookups.load('warehouses')} onMouseDown={() => void lookups.load('warehouses')} onChange={(event) => setForm((current) => ({ ...current, warehouseId: event.target.value }))} options={optionList(lookups.warehouses)} placeholder="Select warehouse" value={form.warehouseId} /></Field>
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
                <FormSection description="Add stock products, spare parts, service work, labour-capable items and backend-defined combo packages. Backend owns price, tax, UOM, stock effects and combo expansion." title="Items / Service Lines">
                    <div className="space-y-3">
                        {form.lines.map((line, index) => (
                            <div className="space-y-2" key={`service-line-${line.clientKey}`}>
                                <div className="flex items-center justify-between">
                                    <p className="text-sm font-bold text-slate-800">Line {index + 1}</p>
                                    <Button onClick={() => removeLine('lines', index)} type="button" variant="secondary">Remove</Button>
                                </div>
                                <LineEditor allowedLineTypes={['spare_part', 'service', 'labour', 'combo']} itemContext={line.lineType === 'labour' ? 'labour' : 'service-lines'} items={line.lineType === 'labour' ? lookups.items.labour : lookups.items['service-lines']} line={line} loadItems={(context) => void lookups.load('items', context)} loadWarehouses={() => void lookups.load('warehouses')} onChange={(next) => {
                                    const normalized = { ...next, requiresStockMovement: next.lineType === 'spare_part' };
                                    if (normalized.lineType === 'labour') {
                                        setForm((current) => ({
                                            ...current,
                                            laborItems: [...current.laborItems, { ...normalized, lineType: 'labour', requiresStockMovement: false }],
                                            lines: current.lines.filter((_, currentIndex) => currentIndex !== index),
                                        }));
                                        return;
                                    }
                                    updateLine('lines', index, normalized);
                                }} warehouses={lookups.warehouses} />
                            </div>
                        ))}
                        {form.laborItems.map((line, index) => (
                            <div className="space-y-2" key={`labour-line-${line.clientKey}`}>
                                <div className="flex items-center justify-between">
                                    <p className="text-sm font-bold text-slate-800">Labour-capable line {index + 1}</p>
                                    <Button onClick={() => removeLine('laborItems', index)} type="button" variant="secondary">Remove</Button>
                                </div>
                                <LineEditor allowedLineTypes={['labour']} itemContext="labour" items={lookups.items.labour} line={line} loadItems={(context) => void lookups.load('items', context)} loadWarehouses={() => void lookups.load('warehouses')} onChange={(next) => updateLine('laborItems', index, { ...next, lineType: 'labour', requiresStockMovement: false })} warehouses={lookups.warehouses} />
                            </div>
                        ))}
                        <div className="flex flex-wrap gap-2">
                            <Button onClick={() => addLine('lines', 'spare_part')} type="button" variant="secondary">Add Product / Part</Button>
                            <Button onClick={() => addLine('lines', 'service')} type="button" variant="secondary">Add Service Item</Button>
                            <Button onClick={() => addLine('laborItems', 'labour')} type="button" variant="secondary">Add Labour Item</Button>
                            <Button onClick={() => addLine('lines', 'combo')} type="button" variant="secondary">Add Combo Package</Button>
                        </div>
                    </div>
                </FormSection>
            ) : null}

            {activeTab === 'non_inventory' ? (
                <FormSection description="Add billable charges that do not affect stock. Inventory products are excluded from this selector and backend validation still owns eligibility." title="Non-Inventory Items">
                    <div className="space-y-3">
                        {form.nonInventoryItems.map((line, index) => (
                            <div className="space-y-2" key={`non-inventory-line-${line.clientKey}`}>
                                <div className="flex items-center justify-between">
                                    <p className="text-sm font-bold text-slate-800">Charge {index + 1}</p>
                                    <Button onClick={() => removeLine('nonInventoryItems', index)} type="button" variant="secondary">Remove</Button>
                                </div>
                                <LineEditor allowedLineTypes={['non_inventory']} itemContext="non-inventory" items={lookups.items['non-inventory']} line={line} loadItems={(context) => void lookups.load('items', context)} loadWarehouses={() => void lookups.load('warehouses')} onChange={(next) => updateLine('nonInventoryItems', index, { ...next, lineType: 'non_inventory', requiresStockMovement: false })} warehouses={lookups.warehouses} />
                            </div>
                        ))}
                        <Button onClick={() => addLine('nonInventoryItems', 'non_inventory')} type="button" variant="secondary">Add Non-Inventory Charge</Button>
                    </div>
                </FormSection>
            ) : null}

            {activeTab === 'labour' ? (
                <FormSection description="Only labour-capable rows appear here. Product, stock and ordinary non-inventory rows are intentionally hidden from technician assignment." title="Labour Assignments">
                    <div className="space-y-3">
                        {form.laborItems.length === 0 ? (
                            <Card className="p-4 text-sm font-semibold text-slate-600">Add a labour item or save a combo package with labour components before assigning technicians.</Card>
                        ) : null}
                        {form.laborItems.map((line, index) => {
                            const assignment = assignmentForLine(line);
                            return (
                                <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 md:grid-cols-[1fr_1fr_120px_120px_1fr]" key={`assignment-${line.clientKey}`}>
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Labour row</p>
                                        <p className="mt-1 font-semibold text-slate-900">{labelFor(lookups.items.labour, line.itemId) ?? (line.description || `Labour line ${index + 1}`)}</p>
                                    </div>
                                    <div className="space-y-2">
                                        <Input onChange={(event) => {
                                            setEmployeeSearch(event.target.value);
                                            void lookups.load('employees', 'service-lines', event.target.value);
                                        }} onFocus={() => void lookups.load('employees', 'service-lines', employeeSearch)} placeholder="Search technician" value={employeeSearch} />
                                        <Select onFocus={() => void lookups.load('employees', 'service-lines', employeeSearch)} onMouseDown={() => void lookups.load('employees', 'service-lines', employeeSearch)} onChange={(event) => setAssignmentForLine(line, { employeeId: event.target.value })} options={optionList(lookups.employees)} placeholder="Technician / Employee" value={assignment?.employeeId ?? ''} />
                                    </div>
                                    <Input min="0" onChange={(event) => setAssignmentForLine(line, { hoursWorked: event.target.value })} placeholder="Hours" type="number" value={assignment?.hoursWorked ?? ''} />
                                    <Input min="0.0001" onChange={(event) => setAssignmentForLine(line, { quantity: event.target.value })} placeholder="Qty" type="number" value={assignment?.quantity ?? line.quantity} />
                                    <Select onChange={(event) => setAssignmentForLine(line, { role: event.target.value })} options={[{ label: 'Technician', value: 'technician' }, { label: 'Lead technician', value: 'lead' }, { label: 'Assistant', value: 'assistant' }, { label: 'Supervisor', value: 'supervisor' }]} value={assignment?.role ?? 'technician'} />
                                    <div className="md:col-span-5"><Textarea onChange={(event) => setAssignmentForLine(line, { notes: event.target.value })} placeholder="Assignment notes" value={assignment?.notes ?? ''} /></div>
                                </div>
                            );
                        })}
                    </div>
                </FormSection>
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
    return <div className="space-y-3">{rows.map((entry) => <Card className="p-4" key={entry.id}><p className="font-semibold text-slate-900">{entry.note}</p><p className="mt-1 text-sm text-slate-500">{entry.actor} · {entry.timestamp}</p></Card>)}</div>;
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
    const [error, setError] = useState<string>();
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let active = true;
        vehicleServiceApi.jobCards.list({ per_page: 25 }).then((response) => active && setJobs(response.data)).catch(() => undefined);

        return () => {
            active = false;
        };
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
                    <Button disabled={saving} type="submit" variant="blue">{saving ? 'Allocating...' : 'Allocate Payment'}</Button>
                </div>
            </FormSection>
        </form>
    );
}
