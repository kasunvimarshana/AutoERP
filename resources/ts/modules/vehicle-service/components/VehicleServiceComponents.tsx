import { useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Textarea } from '../../../shared/components/ui/Textarea';
import {
    customers,
    nonInventoryItems,
    serviceItems,
    serviceTypes,
    sparePartItems,
    technicians,
    uomOptions,
    vehicles,
} from '../mock/vehicleServiceMock';
import type {
    VehicleServiceAuditEntry,
    VehicleServiceDashboardMetric,
    VehicleServiceDiagnostic,
    VehicleServiceInspection,
    VehicleServiceInvoice,
    VehicleServiceJobCard,
    VehicleServiceJobCardLine,
    VehicleServiceLineType,
    VehicleServicePayment,
    VehicleServiceSettings,
    VehicleServiceType,
} from '../types/vehicleService.types';

function options(items: Array<{ id: string; label: string }>) {
    return items.map((item) => ({ label: item.label, value: item.id }));
}

function Label({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
        </div>
    );
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

export function VehicleServiceDashboardCards({ metrics }: { metrics: VehicleServiceDashboardMetric[] }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            {metrics.map((metric) => (
                <Card className="p-5" key={metric.label}>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{metric.label}</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{metric.value}</p>
                    <p className="mt-2 text-xs font-semibold text-slate-500">Backend/mock readonly</p>
                </Card>
            ))}
        </div>
    );
}

export function ServiceTypeForm() {
    return (
        <FormSection description="Service type setup guides workflow defaults only. Backend owns status and validation." title="Service type">
            <div className="grid gap-4 md:grid-cols-2">
                <Field label="Code"><Input placeholder="FULL-SVC" /></Field>
                <Field label="Name"><Input placeholder="Full Service" /></Field>
                <Field label="Status"><Select options={[{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }]} /></Field>
                <Field label="Description"><Textarea placeholder="Workshop service type notes" /></Field>
            </div>
        </FormSection>
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
                { header: 'Workflow', key: 'workflowStatus' },
                { header: 'Updated', key: 'updatedAt' },
                { header: 'Actions', key: 'actions', render: (row) => <Link to={`/vehicle-service/job-cards/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function JobCardHeaderForm() {
    return (
        <div className="grid gap-5 xl:grid-cols-[1fr_340px]">
            <FormSection description="Capture operational intake data. Customer, vehicle, sequence, status, and tenant rules are validated by backend." title="Intake & Header">
                <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Service customer"><div className="flex gap-2"><Select options={options(customers)} placeholder="Select service customer" /><Button variant="secondary">Quick Add</Button></div></Field>
                    <Field label="Billing customer"><Select options={options(customers)} placeholder="May differ from owner/customer" /></Field>
                    <Field label="Payer"><Select options={options(customers)} placeholder="May differ from billing customer" /></Field>
                    <Field label="Vehicle"><div className="flex gap-2"><Select options={options(vehicles)} placeholder="Select vehicle" /><Button variant="secondary">Quick Add</Button></div></Field>
                    <Field label="Service advisor"><Select options={options(technicians)} placeholder="Select advisor" /></Field>
                    <Field label="Supervisor"><Select options={options(technicians)} placeholder="Select supervisor" /></Field>
                    <Field label="Service type"><Select options={serviceTypes.map((type) => ({ label: type.name, value: type.id }))} placeholder="Select type" /></Field>
                    <Field label="Opened date"><Input type="datetime-local" /></Field>
                    <Field label="Expected completion"><Input type="datetime-local" /></Field>
                    <Field label="Odometer / mileage"><Input placeholder="Backend stores as submitted" /></Field>
                    <Field label="Next service date"><Input type="date" /></Field>
                    <Field label="Sequence preview"><Input readOnly value="Generated by backend sequence" /></Field>
                    <div className="md:col-span-2"><Field label="Customer complaint"><Textarea placeholder="Reported complaint, visible symptoms, and intake instructions" /></Field></div>
                    <div className="md:col-span-2"><Field label="Initial diagnosis"><Textarea placeholder="Initial diagnosis and workshop notes" /></Field></div>
                </div>
            </FormSection>
            <PreviewPanel
                rows={[
                    { label: 'Customer validation', value: 'Backend validates eligibility' },
                    { label: 'Vehicle owner', value: 'Backend current ownership summary' },
                    { label: 'Owner/customer mismatch', value: 'Backend warning, not a blocker' },
                    { label: 'Job number', value: 'Backend sequence placeholder' },
                    { label: 'Workflow', value: 'Backend status transition rules' },
                ]}
                status="Mock"
                title="Intake Preview"
            />
        </div>
    );
}

export function VehicleServicePartyContextPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Vehicle owner', value: `${jobCard.partyContext.vehicleOwner.owner.name} (${jobCard.partyContext.vehicleOwner.owner.type})` },
                { label: 'Ownership role', value: `${jobCard.partyContext.vehicleOwner.ownershipType} / ${jobCard.partyContext.vehicleOwner.ownershipRole}` },
                { label: 'Service customer', value: `${jobCard.partyContext.serviceCustomer.name} (${jobCard.partyContext.serviceCustomer.type})` },
                { label: 'Billing customer', value: `${jobCard.partyContext.billingCustomer.name} (${jobCard.partyContext.billingCustomer.type})` },
                { label: 'Payer', value: `${jobCard.partyContext.payer.name} (${jobCard.partyContext.payer.type})` },
                { label: 'Mismatch notice', value: jobCard.partyContext.mismatchNotice || 'No mismatch warning' },
            ]}
            status="Party Context"
            title="Owner / Billing / Payer Context"
        />
    );
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
                { header: 'Invoiceable', key: 'invoiceable', render: (row) => row.invoiceable ? 'Yes' : 'No' },
                { header: 'Backend amount', key: 'backendCalculatedAmount' },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

function LineSection({ description, lineType, rows, title }: { description: string; lineType: VehicleServiceLineType; rows: VehicleServiceJobCardLine[]; title: string }) {
    return (
        <Card className="p-5">
            <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h3 className="text-base font-bold text-slate-950">{title}</h3>
                    <p className="mt-1 text-sm text-slate-500">{description}</p>
                </div>
                <StatusBadge status={lineType === 'spare_part' ? 'Stock impact' : 'No stock impact'} />
            </div>
            <JobCardLineTable rows={rows} />
        </Card>
    );
}

export function ServiceItemsSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection description="Service catalog lines guide workshop work and invoice preview. No direct inventory effect." lineType="service" rows={lines.filter((line) => line.lineType === 'service')} title="Service Items" />;
}

export function SparePartsSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection description="Only spare part / stock item lines are eligible for inventory availability and consumption effects." lineType="spare_part" rows={lines.filter((line) => line.lineType === 'spare_part')} title="Spare Parts / Stock Items" />;
}

export function NonInventoryItemsSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection description="Invoiceable workshop charges that do not affect inventory stock." lineType="non_inventory" rows={lines.filter((line) => line.lineType === 'non_inventory')} title="Non-Inventory Items" />;
}

export function CustomerSuppliedItemsSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection description="Customer-owned materials are tracked for custody and return, with no stock impact." lineType="customer_supplied" rows={lines.filter((line) => line.lineType === 'customer_supplied')} title="Customer-Supplied Items" />;
}

export function ExternalServicesSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection description="Outside provider work can be billable, but does not consume internal workshop stock." lineType="external_service" rows={lines.filter((line) => line.lineType === 'external_service')} title="External Services" />;
}

export function ComboItemsSection({ lines }: { lines: VehicleServiceJobCardLine[] }) {
    return <LineSection description="Combos/packages are expanded by backend into service, stock, labour, and non-stock components." lineType="combo" rows={lines.filter((line) => line.lineType === 'combo')} title="Combo / Package Items" />;
}

export function JobCardLineEditor({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return (
        <div className="space-y-5">
            <FormSection description="Collect line inputs only. Backend resolves pricing, discounts, tax, UOM conversion, stock effects, and combo expansion." title="Add workshop line">
                <div className="grid gap-4 md:grid-cols-[160px_1fr_120px_120px_160px]">
                    <Select options={Object.entries(lineTypeLabels).map(([value, label]) => ({ label, value }))} placeholder="Line type" />
                    <Select options={[...options(serviceItems), ...options(sparePartItems), ...options(nonInventoryItems)]} placeholder="Select item/service" />
                    <Input placeholder="Qty" />
                    <Select options={options(uomOptions)} placeholder="UOM" />
                    <Button variant="blue">Add Line</Button>
                </div>
            </FormSection>
            <ServiceItemsSection lines={jobCard.lines} />
            <SparePartsSection lines={jobCard.lines} />
            <NonInventoryItemsSection lines={jobCard.lines} />
            <CustomerSuppliedItemsSection lines={jobCard.lines} />
            <ExternalServicesSection lines={jobCard.lines} />
            <ComboItemsSection lines={jobCard.lines} />
        </div>
    );
}

export function TechnicianSelector() {
    return <Select options={options(technicians)} placeholder="Select technician" />;
}

export function LabourAssignmentTable({ rows }: { rows: VehicleServiceJobCard['labourAssignments'] }) {
    return (
        <DataTable
            columns={[
                { header: 'Labour item', key: 'labourItem' },
                { header: 'Employee', key: 'employee' },
                { header: 'Assignment', key: 'assignmentType' },
                { header: 'Incentive/share', key: 'incentivePreview' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function LabourAssignmentPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return (
        <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
            <Card className="p-5">
                <div className="mb-4">
                    <h3 className="text-base font-bold text-slate-950">Technician assignments</h3>
                    <p className="mt-1 text-sm text-slate-500">Backend owns employee validation, share rules, incentive preview, and completion status.</p>
                </div>
                <LabourAssignmentTable rows={jobCard.labourAssignments} />
            </Card>
            <FormSection description="Assignment shares and incentives are previewed by backend services." title="Assign labour">
                <div className="space-y-4">
                    <Field label="Labour item"><Select options={options(serviceItems)} placeholder="Select labour item" /></Field>
                    <Field label="Technician"><TechnicianSelector /></Field>
                    <Field label="Assignment type"><Select options={[{ label: 'Primary', value: 'primary' }, { label: 'Support', value: 'support' }, { label: 'Supervisor review', value: 'supervisor' }]} /></Field>
                    <Field label="Share placeholder"><Input placeholder="Backend validates share/incentive" /></Field>
                    <Field label="Supervisor notes"><Textarea placeholder="Review notes or reassignment reason" /></Field>
                    <Button className="w-full" variant="blue">Preview Assignment</Button>
                </div>
            </FormSection>
        </div>
    );
}

export function StockAvailabilityPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Requested quantity', value: jobCard.stockPreview.calculated.requestedQuantity },
                { label: 'Reserved quantity', value: jobCard.stockPreview.calculated.reservedQuantity },
                { label: 'Decision', value: jobCard.stockPreview.calculated.availabilityDecision },
                { label: 'Stock effect', value: jobCard.stockPreview.calculated.stockEffect },
            ]}
            status="Backend Preview"
            title="Stock Availability"
        />
    );
}

export function ServiceInvoicePreviewPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Subtotal', value: jobCard.invoicePreview.calculated.subtotal },
                { label: 'Discount', value: jobCard.invoicePreview.calculated.discountTotal },
                { label: 'Tax', value: jobCard.invoicePreview.calculated.taxTotal },
                { label: 'Grand total', value: jobCard.invoicePreview.calculated.grandTotal },
            ]}
            status="Backend Preview"
            title="Service Invoice Preview"
        />
    );
}

export function ServiceInvoiceDocumentPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Document number', value: jobCard.documentPreview.documentNumber },
                { label: 'Template', value: jobCard.documentPreview.template },
                { label: 'Status', value: jobCard.documentPreview.status },
            ]}
            status="Document"
            title="Document Integration"
        />
    );
}

export function VehicleServiceFinancePostingPanel({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'AR impact', value: jobCard.financePreview.calculated.arImpact },
                { label: 'Eligibility', value: jobCard.financePreview.calculated.eligibility },
                { label: 'Journal status', value: jobCard.financePreview.calculated.journalStatus },
            ]}
            status="Finance"
            title="Finance / AR Posting Preview"
        />
    );
}

export function ServicePaymentPanel({ payments }: { payments: VehicleServicePayment[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Payment', key: 'paymentNumber' },
                { header: 'Payer', key: 'payer' },
                { header: 'Method', key: 'method' },
                { header: 'Amount', key: 'amount' },
                { header: 'Source invoice', key: 'sourceInvoice' },
                { header: 'Allocation preview', key: 'allocationPreview' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={payments}
        />
    );
}

export function DiagnosticsPanel({ rows }: { rows: VehicleServiceDiagnostic[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Diagnostic', key: 'diagnosticNumber' },
                { header: 'Phase', key: 'phase' },
                { header: 'Findings', key: 'findings' },
                { header: 'Recommendation', key: 'recommendation' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function InspectionPanel({ rows }: { rows: VehicleServiceInspection[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Inspection', key: 'inspectionNumber' },
                { header: 'Phase', key: 'phase' },
                { header: 'Result', key: 'result' },
                { header: 'Notes', key: 'notes' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function VehicleServiceWorkflowActions({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return (
        <Card className="p-5">
            <div className="mb-4">
                <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">Workflow Actions</h3>
                <p className="mt-1 text-sm text-slate-500">Buttons represent backend workflow actions. No frontend status calculation is performed.</p>
            </div>
            <div className="flex flex-wrap gap-2">
                <Button variant="secondary">Preview Stock</Button>
                <Button variant="secondary">Generate Invoice</Button>
                <Button variant="secondary">Post Finance</Button>
                <Button variant="secondary">Allocate Payment</Button>
                <Button variant="danger">Cancel Job</Button>
            </div>
            <p className="mt-4 text-sm font-semibold text-slate-600">Current backend status: {jobCard.workflowStatus}</p>
        </Card>
    );
}

export function VehicleServiceActivityTimeline({ rows }: { rows: VehicleServiceAuditEntry[] }) {
    return (
        <div className="space-y-3">
            {rows.map((entry) => (
                <Card className="p-4" key={entry.id}>
                    <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p className="font-semibold text-slate-900">{entry.note}</p>
                            <p className="mt-1 text-sm text-slate-500">{entry.actor}</p>
                        </div>
                        <div className="text-sm text-slate-500">{entry.timestamp}</div>
                    </div>
                </Card>
            ))}
        </div>
    );
}

export function VehicleServiceSettingsForm({ settings }: { settings: VehicleServiceSettings }) {
    return (
        <FormSection description="VehicleService settings remain module-specific. Global configuration stays outside this module." title="Workshop settings">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Default warehouse"><Input defaultValue={settings.defaultWarehouse} /></Field>
                <Field label="Default tax group"><Input defaultValue={settings.defaultTaxGroup} /></Field>
                <Field label="Job card sequence"><Input defaultValue={settings.jobCardSequence} /></Field>
                <Field label="Service invoice sequence"><Input defaultValue={settings.invoiceSequence} /></Field>
                <Field label="Document definition"><Input defaultValue={settings.documentDefinition} /></Field>
                <Field label="Stock timing"><Input defaultValue={settings.stockConsumptionTiming} /></Field>
                <Field label="Customer supplied items"><Select defaultValue={String(settings.allowCustomerSuppliedItems)} options={[{ label: 'Allowed', value: 'true' }, { label: 'Not allowed', value: 'false' }]} /></Field>
                <Field label="External services"><Select defaultValue={String(settings.allowExternalServices)} options={[{ label: 'Allowed', value: 'true' }, { label: 'Not allowed', value: 'false' }]} /></Field>
                <Field label="Negative stock"><Select defaultValue={String(settings.allowNegativeStock)} options={[{ label: 'Blocked', value: 'false' }, { label: 'Allowed by backend', value: 'true' }]} /></Field>
            </div>
        </FormSection>
    );
}

export function JobCardReview({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    return (
        <div className="grid gap-5 xl:grid-cols-3">
            <PreviewPanel
                rows={[
                    { label: 'Service customer', value: jobCard.partyContext.serviceCustomer.name },
                    { label: 'Billing customer', value: jobCard.partyContext.billingCustomer.name },
                    { label: 'Vehicle', value: jobCard.vehicle },
                    { label: 'Service type', value: jobCard.serviceType },
                    { label: 'Workflow', value: jobCard.workflowStatus },
                ]}
                title="Job Summary"
            />
            <VehicleServicePartyContextPanel jobCard={jobCard} />
            <StockAvailabilityPanel jobCard={jobCard} />
            <ServiceInvoicePreviewPanel jobCard={jobCard} />
        </div>
    );
}

export function JobCardForm({ jobCard }: { jobCard: VehicleServiceJobCard }) {
    const tabs = [
        { label: 'Intake & Header', value: 'intake' },
        { label: 'Job Lines', value: 'lines' },
        { label: 'Labour & Assignment', value: 'labour' },
        { label: 'Review & Preview', value: 'review' },
    ];
    const [activeTab, setActiveTab] = useState('intake');

    return (
        <div className="space-y-5">
            <Card className="p-5">
                <Tabs active={activeTab} items={tabs} onChange={setActiveTab} trailing={<StatusBadge status="Mock backed" />} />
            </Card>
            {activeTab === 'intake' ? <JobCardHeaderForm /> : null}
            {activeTab === 'lines' ? <JobCardLineEditor jobCard={jobCard} /> : null}
            {activeTab === 'labour' ? <LabourAssignmentPanel jobCard={jobCard} /> : null}
            {activeTab === 'review' ? <JobCardReview jobCard={jobCard} /> : null}
        </div>
    );
}

export function ServiceInvoiceTable({ rows }: { rows: VehicleServiceInvoice[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Invoice', key: 'invoiceNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-service/invoices/${row.id}`}>{row.invoiceNumber}</Link> },
                { header: 'Job card', key: 'jobCardNumber' },
                { header: 'Billing customer', key: 'billingCustomer' },
                { header: 'Preview total', key: 'previewTotal' },
                { header: 'Document', key: 'documentStatus' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Updated', key: 'updatedAt' },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function PaymentCreateForm() {
    return (
        <FormSection description="Collect service payment input. Backend owns allocation, unallocated balance, AR, and finance posting." title="Service payment">
            <div className="grid gap-4 md:grid-cols-2">
                <Field label="Job card / invoice"><Input placeholder="Select invoiceable job or service invoice" /></Field>
                <Field label="Payment method"><Select options={[{ label: 'Cash', value: 'cash' }, { label: 'Bank', value: 'bank' }, { label: 'Card', value: 'card' }, { label: 'Online', value: 'online' }]} /></Field>
                <Field label="Payment date"><Input type="date" /></Field>
                <Field label="Amount"><Input placeholder="Backend validates amount and allocation" /></Field>
                <Field label="Reference"><Input placeholder="Bank/card/check/reference" /></Field>
                <Field label="Allocation preview"><Input readOnly value="Backend calculated" /></Field>
                <div className="md:col-span-2"><Field label="Notes"><Textarea placeholder="Payment remarks" /></Field></div>
            </div>
        </FormSection>
    );
}

export function VehicleServicePageHeader({ actions, subtitle, title }: { actions?: ReactNode; subtitle?: string; title: string }) {
    return <PageHeader actions={actions} eyebrow="Vehicle Service" subtitle={subtitle} title={title} />;
}
