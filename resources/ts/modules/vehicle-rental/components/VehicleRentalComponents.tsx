import { useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
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
import { customers, drivers, providers, ratePlans, vehicles } from '../mock/vehicleRentalMock';
import type {
    VehicleRentalAgreement,
    VehicleRentalAuditEntry,
    VehicleRentalBreakdown,
    VehicleRentalDashboardMetric,
    VehicleRentalInvoice,
    VehicleRentalPayment,
    VehicleRentalProviderPayable,
    VehicleRentalReplacement,
    VehicleRentalRunningChart,
    VehicleRentalSettings,
} from '../types/vehicleRental.types';

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
                    <p className="mt-2 text-xs font-semibold text-slate-500">Backend/mock readonly</p>
                </Card>
            ))}
        </div>
    );
}

export function VehicleAvailabilityTable() {
    return (
        <DataTable
            columns={[
                { header: 'Vehicle', key: 'vehicle' },
                { header: 'Source', key: 'source' },
                { header: 'Availability', key: 'availability', render: (row) => <StatusBadge status={String(row.availability)} /> },
                { header: 'Window', key: 'window' },
                { header: 'Backend decision', key: 'decision' },
            ]}
            getRowKey={(row) => row.id}
            rows={[
                { availability: 'Available', decision: 'Backend available', id: 'av-001', source: 'Own fleet', vehicle: 'WP CAD-4521 | Toyota HiAce', window: 'Requested range' },
                { availability: 'Unavailable', decision: 'Backend conflict found', id: 'av-002', source: 'Own fleet', vehicle: 'WP KA-7781 | Nissan Caravan', window: 'Requested range' },
                { availability: 'Provider', decision: 'Backend provider confirmed', id: 'av-003', source: 'External provider', vehicle: 'CP CAB-9410 | Mitsubishi L200', window: 'Requested range' },
            ]}
        />
    );
}

export function VehicleAvailabilityCalendar() {
    return (
        <PreviewPanel
            status="Availability"
            subtitle="Calendar blocks are backend/mock returned availability states. Frontend does not calculate booking overlaps."
            title="Availability Calendar"
        >
            <div className="grid gap-3 md:grid-cols-7">
                {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((day, index) => (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-3" key={day}>
                        <p className="text-xs font-bold uppercase text-slate-400">{day}</p>
                        <p className="mt-2 text-sm font-semibold text-slate-700">{index === 2 ? 'Booked' : index === 4 ? 'Provider' : 'Available'}</p>
                    </div>
                ))}
            </div>
        </PreviewPanel>
    );
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

export function RentalRateRulesTable({ agreement }: { agreement: VehicleRentalAgreement }) {
    return (
        <DataTable
            columns={[
                { header: 'Rule', key: 'ruleName' },
                { header: 'Type', key: 'ruleType' },
                { header: 'Scope', key: 'scope' },
                { header: 'Backend value', key: 'valuePreview' },
            ]}
            getRowKey={(row) => row.id}
            rows={agreement.rateRules}
        />
    );
}

export function RentalExtraChargesTable() {
    return (
        <DataTable
            columns={[
                { header: 'Charge', key: 'charge' },
                { header: 'Scope', key: 'scope' },
                { header: 'Quantity', key: 'quantity' },
                { header: 'Backend amount', key: 'amount' },
            ]}
            getRowKey={(row) => row.id}
            rows={[
                { amount: 'Backend calculated', charge: 'Damage / cleaning placeholder', id: 'extra-001', quantity: 'Backend quantity', scope: 'customer' },
                { amount: 'Backend calculated', charge: 'Provider handling placeholder', id: 'extra-002', quantity: 'Backend quantity', scope: 'provider' },
            ]}
        />
    );
}

export function RentalBillingPreviewPanel({ agreement }: { agreement: VehicleRentalAgreement }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Rental charge', value: agreement.billingPreview.calculated.rentalCharge },
                { label: 'Discount', value: agreement.billingPreview.calculated.discountTotal },
                { label: 'Tax', value: agreement.billingPreview.calculated.taxTotal },
                { label: 'Grand total', value: agreement.billingPreview.calculated.grandTotal },
                { label: 'Provider payable', value: agreement.billingPreview.calculated.providerPayable },
            ]}
            status="Backend Preview"
            title="Rental Billing Preview"
        />
    );
}

export function RentalProviderPayablePanel({ rows }: { rows: VehicleRentalProviderPayable[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Payable', key: 'payableNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-rental/provider-payables/${row.id}`}>{row.payableNumber}</Link> },
                { header: 'Agreement', key: 'agreementNumber' },
                { header: 'Provider', key: 'provider' },
                { header: 'Payable preview', key: 'payablePreview' },
                { header: 'Payment', key: 'paymentStatus' },
                { header: 'Finance', key: 'financeStatus' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function RentalProviderPanel({ agreement }: { agreement: VehicleRentalAgreement }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Vehicle source', value: agreement.vehicleSource },
                { label: 'Provider', value: agreement.provider },
                { label: 'Provider payable', value: agreement.billingPreview.calculated.providerPayable },
                { label: 'AP impact', value: agreement.financePreview.calculated.apImpact },
            ]}
            status="Provider"
            title="Driver / Provider"
        />
    );
}

export function RentalAgreementTermsForm() {
    return (
        <FormSection description="Rental terms are collected here. Backend owns duration, usage, deposit, and billing interpretation." title="Rental Terms">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Rental mode"><Select options={[{ label: 'With driver', value: 'with_driver' }, { label: 'Without driver', value: 'without_driver' }]} /></Field>
                <Field label="Rental unit"><Select options={[{ label: 'KM', value: 'km' }, { label: 'Hour', value: 'hour' }, { label: 'Day', value: 'day' }, { label: 'Week', value: 'week' }, { label: 'Month', value: 'month' }]} /></Field>
                <Field label="Included KM/hours"><Input placeholder="Backend validates included usage" /></Field>
                <Field label="Deposit / advance"><Input placeholder="Backend owns advance balance" /></Field>
                <div className="md:col-span-2"><Field label="Terms / notes"><Textarea placeholder="Rental terms, customer instructions, pickup/dropoff notes" /></Field></div>
            </div>
        </FormSection>
    );
}

export function RentalRatePlanPanel() {
    return (
        <FormSection description="Rate inputs are previewed by backend. Frontend does not calculate rental charges." title="Rates & Charges">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Rate plan"><Select options={options(ratePlans)} placeholder="Select rate plan" /></Field>
                <Field label="Base rate"><Input placeholder="Backend resolves/validates" /></Field>
                <Field label="Overtime rate"><Input placeholder="Backend preview only" /></Field>
                <Field label="Night/weekend/double-rate"><Input placeholder="Backend preview only" /></Field>
                <Field label="Tax group"><Input placeholder="Tax group" /></Field>
                <Field label="Discount placeholder"><Input placeholder="Backend calculates" /></Field>
            </div>
        </FormSection>
    );
}

export function RentalAgreementForm({ agreement }: { agreement: VehicleRentalAgreement }) {
    const [activeTab, setActiveTab] = useState('customer');
    const tabs = [
        { label: 'Customer & Vehicle', value: 'customer' },
        { label: 'Rental Terms', value: 'terms' },
        { label: 'Rates & Charges', value: 'rates' },
        { label: 'Driver / Provider', value: 'provider' },
        { label: 'Review & Preview', value: 'review' },
    ];

    return (
        <div className="space-y-5">
            <Card className="p-5"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} trailing={<StatusBadge status="Mock backed" />} /></Card>
            {activeTab === 'customer' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                    <FormSection description="Customer, vehicle, date range, and availability are validated by backend." title="Customer & Vehicle">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Customer"><div className="flex gap-2"><Select options={options(customers)} placeholder="Select customer" /><Button variant="secondary">Quick Add</Button></div></Field>
                            <Field label="Vehicle"><Select options={options(vehicles)} placeholder="Select vehicle" /></Field>
                            <Field label="Rental start"><Input type="datetime-local" /></Field>
                            <Field label="Rental end"><Input type="datetime-local" /></Field>
                            <Field label="Pickup location"><Input placeholder="Pickup location" /></Field>
                            <Field label="Dropoff location"><Input placeholder="Dropoff location" /></Field>
                        </div>
                    </FormSection>
                    <PreviewPanel rows={[
                        { label: 'Availability', value: agreement.availabilityPreview.calculated.availabilityDecision },
                        { label: 'Conflicts', value: agreement.availabilityPreview.calculated.conflicts },
                        { label: 'Replacement', value: agreement.availabilityPreview.calculated.replacementOption },
                    ]} status="Backend Preview" title="Vehicle Availability" />
                </div>
            ) : null}
            {activeTab === 'terms' ? <RentalAgreementTermsForm /> : null}
            {activeTab === 'rates' ? <div className="space-y-5"><RentalRatePlanPanel /><RentalRateRulesTable agreement={agreement} /><RentalExtraChargesTable /></div> : null}
            {activeTab === 'provider' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                    <FormSection description="Driver/provider selection is optional depending on rental mode and vehicle source." title="Driver / Provider">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Driver / employee"><Select options={options(drivers)} placeholder="Select driver if with-driver" /></Field>
                            <Field label="External provider"><Select options={options(providers)} placeholder="Select provider if external vehicle" /></Field>
                            <Field label="Provider payable preview"><Input readOnly value="Backend calculated" /></Field>
                            <Field label="Provider payment eligibility"><Input readOnly value="Backend workflow checked" /></Field>
                        </div>
                    </FormSection>
                    <RentalProviderPanel agreement={agreement} />
                </div>
            ) : null}
            {activeTab === 'review' ? <div className="grid gap-5 xl:grid-cols-3"><RentalBillingPreviewPanel agreement={agreement} /><RentalProviderPanel agreement={agreement} /><VehicleRentalFinancePostingPanel agreement={agreement} /></div> : null}
        </div>
    );
}

export function RunningChartLineTable({ chart }: { chart: VehicleRentalRunningChart }) {
    return (
        <DataTable
            columns={[
                { header: 'Line', key: 'lineNumber' },
                { header: 'Vehicle', key: 'vehicle' },
                { header: 'Driver', key: 'driver' },
                { header: 'Start reading', key: 'startReading' },
                { header: 'End reading', key: 'endReading' },
                { header: 'Usage', key: 'usagePreview' },
                { header: 'Charge', key: 'chargePreview' },
                { header: 'Provider cost', key: 'providerCostPreview' },
            ]}
            getRowKey={(row) => row.id}
            rows={chart.lines}
        />
    );
}

export function RunningChartForm() {
    return (
        <FormSection description="Usage readings are collected here. Backend owns usage validation and billing preview." title="Running Chart">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Agreement"><Input placeholder="Select agreement" /></Field>
                <Field label="Vehicle"><Select options={options(vehicles)} /></Field>
                <Field label="Driver"><Select options={options(drivers)} /></Field>
                <Field label="Start date/time"><Input type="datetime-local" /></Field>
                <Field label="End date/time"><Input type="datetime-local" /></Field>
                <Field label="Start reading"><Input placeholder="Backend validates meter reading" /></Field>
                <Field label="End reading"><Input placeholder="Backend validates meter reading" /></Field>
                <Field label="Usage quantity"><Input placeholder="Backend calculated/validated" /></Field>
                <Field label="Billing preview"><Input readOnly value="Backend calculated" /></Field>
                <div className="xl:col-span-3"><Field label="Notes"><Textarea placeholder="Fuel, route, expense, breakdown, or customer notes" /></Field></div>
            </div>
        </FormSection>
    );
}

export function RunningChartBillingPreviewPanel({ chart }: { chart: VehicleRentalRunningChart }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Rental charge', value: chart.billingPreview.calculated.rentalCharge },
                { label: 'Tax', value: chart.billingPreview.calculated.taxTotal },
                { label: 'Provider payable', value: chart.providerPayablePreview },
                { label: 'Grand total', value: chart.billingPreview.calculated.grandTotal },
            ]}
            status="Backend Preview"
            title="Running Chart Billing Preview"
        />
    );
}

export function RentalInvoicePanel({ rows }: { rows: VehicleRentalInvoice[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Invoice', key: 'invoiceNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vehicle-rental/invoices/${row.id}`}>{row.invoiceNumber}</Link> },
                { header: 'Agreement', key: 'sourceAgreement' },
                { header: 'Customer', key: 'customer' },
                { header: 'Billing', key: 'billingPreview' },
                { header: 'Document', key: 'documentStatus' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function RentalPaymentPanel({ rows }: { rows: VehicleRentalPayment[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Payment', key: 'paymentNumber' },
                { header: 'Customer', key: 'customer' },
                { header: 'Method', key: 'method' },
                { header: 'Amount', key: 'amount' },
                { header: 'Invoice', key: 'sourceInvoice' },
                { header: 'Allocation', key: 'allocationPreview' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function ReplacementVehiclePanel({ rows }: { rows: VehicleRentalReplacement[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Replacement', key: 'replacementNumber' },
                { header: 'Agreement', key: 'agreementNumber' },
                { header: 'Original', key: 'originalVehicle' },
                { header: 'Replacement vehicle', key: 'replacementVehicle' },
                { header: 'Reason', key: 'reason' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function BreakdownPanel({ rows }: { rows: VehicleRentalBreakdown[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Breakdown', key: 'breakdownNumber' },
                { header: 'Agreement', key: 'agreementNumber' },
                { header: 'Vehicle', key: 'vehicle' },
                { header: 'Resolution', key: 'resolution' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function VehicleRentalFinancePostingPanel({ agreement }: { agreement: VehicleRentalAgreement }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'AR impact', value: agreement.financePreview.calculated.arImpact },
                { label: 'AP impact', value: agreement.financePreview.calculated.apImpact },
                { label: 'Eligibility', value: agreement.financePreview.calculated.eligibility },
                { label: 'Journal status', value: agreement.financePreview.calculated.journalStatus },
            ]}
            status="Finance"
            title="Finance AR/AP Posting Preview"
        />
    );
}

export function VehicleRentalWorkflowActions({ agreement }: { agreement: VehicleRentalAgreement }) {
    return (
        <Card className="p-5">
            <div className="mb-4">
                <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">Workflow Actions</h3>
                <p className="mt-1 text-sm text-slate-500">Actions call backend workflow endpoints. No frontend workflow status calculation is performed.</p>
            </div>
            <div className="flex flex-wrap gap-2">
                <Button variant="secondary">Check Availability</Button>
                <Button variant="secondary">Preview Billing</Button>
                <Button variant="secondary">Generate Invoice</Button>
                <Button variant="secondary">Create Provider Payable</Button>
                <Button variant="secondary">Post Finance</Button>
                <Button variant="danger">Cancel Agreement</Button>
            </div>
            <p className="mt-4 text-sm font-semibold text-slate-600">Current backend status: {agreement.workflowStatus}</p>
        </Card>
    );
}

export function VehicleRentalActivityTimeline({ rows }: { rows: VehicleRentalAuditEntry[] }) {
    return (
        <div className="space-y-3">
            {rows.map((entry) => (
                <Card className="p-4" key={`vehicle-rental-activity-${entry.id}-${entry.timestamp}`}>
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

export function VehicleRentalSettingsForm({ settings }: { settings: VehicleRentalSettings }) {
    return (
        <FormSection description="Module settings for rental sequences, document definitions, provider flow, and default financial mappings." title="Rental Settings">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Agreement sequence"><Input defaultValue={settings.agreementSequence} /></Field>
                <Field label="Running chart sequence"><Input defaultValue={settings.runningChartSequence} /></Field>
                <Field label="Invoice sequence"><Input defaultValue={settings.invoiceSequence} /></Field>
                <Field label="Default rate plan"><Input defaultValue={settings.defaultRatePlan} /></Field>
                <Field label="Default tax group"><Input defaultValue={settings.defaultTaxGroup} /></Field>
                <Field label="Invoice document definition"><Input defaultValue={settings.invoiceDocumentDefinition} /></Field>
                <Field label="Provider payable account"><Input defaultValue={settings.defaultProviderPayableAccount} /></Field>
                <Field label="External provider vehicles"><Select defaultValue={String(settings.allowExternalProviderVehicles)} options={[{ label: 'Allowed', value: 'true' }, { label: 'Blocked', value: 'false' }]} /></Field>
                <Field label="Replacement vehicle"><Select defaultValue={String(settings.allowReplacementVehicle)} options={[{ label: 'Allowed', value: 'true' }, { label: 'Blocked', value: 'false' }]} /></Field>
            </div>
        </FormSection>
    );
}
