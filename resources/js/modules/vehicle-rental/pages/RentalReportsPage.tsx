import { useState, type ReactNode } from 'react';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { getRentalReportSummary } from '../vehicleRentalApi';

export function RentalReportsPage() {
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
                description="Operational usage, customer revenue, owner cost, outstanding balances, and gross margin derived from authoritative transactions."
            />
            <div className="mb-5 grid gap-4 md:grid-cols-2">
                <Input label="From" type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} />
                <Input label="To" type="date" min={dateFrom || undefined} value={dateTo} onChange={(event) => setDateTo(event.target.value)} />
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
                            Detailed tax, ledger, cheque, and bank results remain in their owning modules and reconcile back to the Rental invoices, payables, receipts, and payments.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <LinkButton variant="secondary" to="/invoices">Invoice register</LinkButton>
                            <LinkButton variant="secondary" to="/payments">Payment register</LinkButton>
                            <LinkButton variant="secondary" to="/finance/ledger">General ledger</LinkButton>
                            <LinkButton variant="secondary" to="/finance/bank-reconciliations">Bank reconciliation</LinkButton>
                        </div>
                    </Panel>
                </div>
            )}
        </>
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
