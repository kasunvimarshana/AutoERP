import { useState, type ReactNode } from 'react';
import { useLocation } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { financePermissions } from '@/modules/finance/financePermissions';
import { invoicePermissions } from '@/modules/invoice/invoicePermissions';
import { paymentPermissions } from '@/modules/payment/paymentPermissions';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { getRentalReportSummary } from '../vehicleRentalApi';
import { VehicleRentalReportPage, type VehicleRentalReportKind } from './VehicleRentalReportPage';

interface ReportRoute {
    kind: VehicleRentalReportKind;
    reportKey: string;
}

const REPORT_ROUTES: Record<string, ReportRoute> = {
    'running-chart': { kind: 'running-chart', reportKey: 'vehicle-rental/running-chart' },
    'chart-exceptions': { kind: 'chart-exceptions', reportKey: 'vehicle-rental/chart-exceptions' },
    'customer-invoices': { kind: 'customer-invoices', reportKey: 'vehicle-rental/customer-invoices' },
    'owner-vouchers': { kind: 'owner-vouchers', reportKey: 'vehicle-rental/owner-vouchers' },
    'rental-history': { kind: 'rental-history', reportKey: 'vehicle-rental/rental-history' },
};

export function RentalReportsPage() {
    const { pathname } = useLocation();
    const reportSlug = pathname.split('/').filter(Boolean)[2] ?? '';
    const report = REPORT_ROUTES[reportSlug];

    return report
        ? <VehicleRentalReportPage reportKey={report.reportKey} kind={report.kind} />
        : <RentalReportsOverview />;
}

function RentalReportsOverview() {
    const auth = useAuth();
    const canViewInvoices = hasPermission(auth, invoicePermissions.view);
    const canViewPayments = hasPermission(auth, paymentPermissions.view);
    const canViewLedger = hasPermission(auth, financePermissions.reportsView);
    const canViewBankReconciliation = hasPermission(auth, financePermissions.bankReconciliationsView);
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const report = useApi(
        (signal) => getRentalReportSummary({
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, signal),
        [dateFrom, dateTo],
    );
    const data = report.data;

    return (
        <>
            <ContentHeader
                title="Vehicle rental reports"
                description="Operational evidence, customer revenue, owner cost and financial documents derived from authoritative Vehicle Rental transactions."
            />
            <Panel title="Phase 1 operational and billing reports">
                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <ReportLink
                        to="/vehicle-rental/reports/running-chart"
                        title="Daily Running Chart Report"
                        description="Physical usage, customer and owner context, kilometres, driver overtime and night-outs."
                    />
                    <ReportLink
                        to="/vehicle-rental/reports/chart-exceptions"
                        title="Missing / Duplicate Running Charts"
                        description="Assignment dates without current charts and duplicate assignment or vehicle evidence."
                    />
                    <ReportLink
                        to="/vehicle-rental/reports/customer-invoices"
                        title="Customer Invoice Register"
                        description="Posted customer invoices traced to rental calculations and Running Charts."
                    />
                    <ReportLink
                        to="/vehicle-rental/reports/owner-vouchers"
                        title="Owner Payable Voucher Register"
                        description="Posted self-billed owner settlements traced to calculations and Running Charts."
                    />
                    <ReportLink
                        to="/vehicle-rental/reports/rental-history"
                        title="Vehicle Rental History"
                        description="Assignments, owner source, driver mode, replacement lineage and finalized usage totals."
                    />
                </div>
            </Panel>

            <div className="my-5 grid gap-4 md:grid-cols-2">
                <Input label="Summary from" type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} />
                <Input label="Summary to" type="date" min={dateFrom || undefined} value={dateTo} onChange={(event) => setDateTo(event.target.value)} />
            </div>
            <ErrorAlert error={report.error} inline />
            {report.loading || !data ? <LoadingState /> : (
                <div className="space-y-5">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Metric title="Finalized running charts" value={String(data.running_charts.count)} />
                        <Metric title="Commercial KM" value={data.running_charts.commercial_km} />
                        <Metric title="Customer billing subtotal" money={data.customer.subtotal_amount} />
                        <Metric title="Owner settlement subtotal" money={data.owner.subtotal_amount} />
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Metric title="Gross margin before tax" money={data.gross_margin_before_tax} />
                        <Metric title="Customer outstanding" money={data.customer.outstanding_amount} />
                        <Metric title="Owner outstanding" money={data.owner.outstanding_amount} />
                    </div>
                    <div className="grid gap-5 lg:grid-cols-2">
                        <Panel title="Customer revenue side">
                            <ReportRows
                                calculations={data.customer.calculation_count}
                                documents={data.customer.financial_document_count}
                                documentTotal={data.customer.document_total}
                                outstanding={data.customer.outstanding_amount}
                            />
                            <div className="mt-4 flex flex-wrap gap-2">
                                <LinkButton variant="secondary" to="/vehicle-rental/customer-invoices">Customer invoices</LinkButton>
                                <LinkButton variant="secondary" to="/vehicle-rental/customer-receipts">Customer receipts</LinkButton>
                            </div>
                        </Panel>
                        <Panel title="Owner cost side">
                            <ReportRows
                                calculations={data.owner.calculation_count}
                                documents={data.owner.financial_document_count}
                                documentTotal={data.owner.document_total}
                                outstanding={data.owner.outstanding_amount}
                            />
                            <div className="mt-4 flex flex-wrap gap-2">
                                <LinkButton variant="secondary" to="/vehicle-rental/owner-settlements">Owner settlements</LinkButton>
                                <LinkButton variant="secondary" to="/vehicle-rental/owner-payments">Owner payments</LinkButton>
                            </div>
                        </Panel>
                    </div>
                    <Panel title="Accounting and reconciliation">
                        <p className="text-sm text-slate-700">
                            Detailed tax, ledger, cheque and bank results remain in their owning modules and reconcile back to Rental invoices, payables, receipts and payments.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            {canViewInvoices && <LinkButton variant="secondary" to="/invoices">Invoice register</LinkButton>}
                            {canViewPayments && <LinkButton variant="secondary" to="/payments">Payment register</LinkButton>}
                            {canViewLedger && <LinkButton variant="secondary" to="/finance/ledger">General ledger</LinkButton>}
                            {canViewBankReconciliation && <LinkButton variant="secondary" to="/finance/bank-reconciliations">Bank reconciliation</LinkButton>}
                        </div>
                    </Panel>
                </div>
            )}
        </>
    );
}

function ReportLink({ to, title, description }: { to: string; title: string; description: string }) {
    return (
        <LinkButton to={to} variant="secondary" className="h-auto min-h-24 items-start justify-start whitespace-normal p-4 text-left">
            <span>
                <span className="block font-semibold text-slate-900">{title}</span>
                <span className="mt-1 block text-sm font-normal text-slate-600">{description}</span>
            </span>
        </LinkButton>
    );
}

function Metric({ title, value, money }: { title: string; value?: string; money?: string }) {
    return (
        <Panel title={title}>
            <div className="text-2xl font-semibold text-slate-900">
                {money !== undefined ? <MoneyDisplay value={money} /> : value}
            </div>
        </Panel>
    );
}

function ReportRows({
    calculations,
    documents,
    documentTotal,
    outstanding,
}: {
    calculations: number;
    documents: number;
    documentTotal: string;
    outstanding: string;
}) {
    return (
        <dl className="space-y-3 text-sm">
            <Row label="Prepared periods" value={String(calculations)} />
            <Row label="Posted documents" value={String(documents)} />
            <Row label="Document total" value={<MoneyDisplay value={documentTotal} />} />
            <Row label="Outstanding" value={<MoneyDisplay value={outstanding} />} />
        </dl>
    );
}

function Row({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4">
            <dt className="text-slate-600">{label}</dt>
            <dd className="font-medium text-slate-900">{value}</dd>
        </div>
    );
}
