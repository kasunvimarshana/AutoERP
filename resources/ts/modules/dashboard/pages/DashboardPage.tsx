import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { DateDisplay, EmptyState, LoadingState, MoneyDisplay, PageHeader, PrimaryLink, StatCard, StatusBadge, TableCard } from '../../../shared/components/erp/ErpUi';
import { invoiceApi } from '../../invoice/services/invoiceApi';
import type { Invoice } from '../../invoice/types/invoice.types';
import { itemApi } from '../../item/services/itemApi';
import { paymentApi } from '../../payment/services/paymentApi';
import type { Payment } from '../../payment/types/payment.types';
import { purchaseApi } from '../../purchase/services/purchaseApi';
import type { PurchaseDashboard, PurchaseOrder } from '../../purchase/types/purchase.types';
import { vehicleServiceApi } from '../../vehicleService/services/vehicleServiceApi';
import type { Dashboard as ServiceDashboard, JobCard } from '../../vehicleService/types/vehicleService.types';

type DashboardState = {
    invoices: Invoice[];
    itemWatchCount: number;
    payments: Payment[];
    purchase?: PurchaseDashboard;
    purchaseOrders: PurchaseOrder[];
    receivableOutstanding: number;
    payableOutstanding: number;
    service?: ServiceDashboard;
    serviceJobs: JobCard[];
};

export function DashboardPage() {
    const [data, setData] = useState<DashboardState | null>(null);
    const [error, setError] = useState('');

    useEffect(() => {
        let active = true;
        void Promise.all([
            purchaseApi.dashboard(),
            vehicleServiceApi.dashboard(),
            invoiceApi.list({ page: 1, perPage: 8 }),
            paymentApi.list({ page: 1, perPage: 8 }),
            purchaseApi.listOrders({ page: 1, perPage: 6 }),
            vehicleServiceApi.listJobs({ page: 1, perPage: 6 }),
            paymentApi.lookup('receivable-invoices'),
            paymentApi.lookup('payable-invoices'),
            itemApi.list({ page: 1, perPage: 100 }),
        ])
            .then(([purchase, service, invoices, payments, purchaseOrders, serviceJobs, receivables, payables, items]) => {
                if (!active) return;
                setData({
                    invoices: invoices.invoices,
                    itemWatchCount: items.items.filter((item) => item.trackInventory && Number(item.reorderLevel) > 0).length,
                    payments: payments.payments,
                    payableOutstanding: payables.reduce((sum, invoice) => sum + Number(invoice.balance_total || 0), 0),
                    purchase,
                    purchaseOrders: purchaseOrders.items,
                    receivableOutstanding: receivables.reduce((sum, invoice) => sum + Number(invoice.balance_total || 0), 0),
                    service,
                    serviceJobs: serviceJobs.items,
                });
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load dashboard.');
            });

        return () => {
            active = false;
        };
    }, []);

    const unpaidInvoices = useMemo(() => data?.invoices.filter((invoice) => Number(invoice.balanceDue) > 0).length ?? 0, [data]);

    if (!data && !error) return <LoadingState label="Loading ERP dashboard" />;
    if (!data) return <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>;

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<QuickActions />}
                eyebrow="ERP Workspace"
                subtitle="Operational cockpit for procurement, workshop jobs, settlement, and inventory attention."
                title="Dashboard"
            />

            <div className="grid gap-4 xl:grid-cols-4">
                <StatCard label="Open purchase orders" value={data.purchase?.open_purchase_orders ?? 0} />
                <StatCard label="Open service jobs" value={data.service?.open_jobs ?? 0} />
                <StatCard label="Outstanding receivables" value={<MoneyDisplay value={data.receivableOutstanding} />} />
                <StatCard label="Outstanding payables" value={<MoneyDisplay value={data.payableOutstanding} />} />
            </div>

            <div className="grid gap-5 xl:grid-cols-4">
                <MetricPanel title="Purchasing">
                    <Metric label="Open POs" value={data.purchase?.open_purchase_orders ?? 0} to="/purchase/orders" />
                    <Metric label="Pending GRNs" value={data.purchase?.pending_grns ?? 0} to="/purchase/grns" />
                    <Metric label="Uninvoiced purchases" value={data.purchase?.unpaid_purchase_invoices.count ?? 0} to="/invoices" />
                </MetricPanel>
                <MetricPanel title="Vehicle Service">
                    <Metric label="Open jobs" value={data.service?.open_jobs ?? 0} to="/vehicle-service/jobs" />
                    <Metric label="Completed jobs" value={data.service?.completed_jobs ?? 0} to="/vehicle-service/jobs" />
                    <Metric label="Uninvoiced jobs" value={data.service?.pending_invoice_jobs ?? 0} to="/vehicle-service/jobs" />
                </MetricPanel>
                <MetricPanel title="Finance">
                    <Metric label="Unpaid invoices" value={unpaidInvoices} to="/invoices" />
                    <Metric label="Receivables" value={<MoneyDisplay value={data.receivableOutstanding} />} to="/invoices" />
                    <Metric label="Payables" value={<MoneyDisplay value={data.payableOutstanding} />} to="/payments" />
                </MetricPanel>
                <MetricPanel title="Inventory">
                    <Metric label="Reorder watchlist" value={data.itemWatchCount} to="/items" />
                    <Metric label="Low stock items" value="Not connected" />
                    <Metric label="Out of stock items" value="Not connected" />
                </MetricPanel>
            </div>

            <div className="grid gap-5 xl:grid-cols-2">
                <ActivityCard title="Latest purchase activity">
                    {data.purchaseOrders.length ? data.purchaseOrders.map((order) => <ActivityRow amount={order.grandTotal} date={order.orderDate} key={order.id} label={order.poNumber} status={order.status} to={`/purchase/orders/${order.id}`} />) : <EmptyState title="No recent purchase orders" />}
                </ActivityCard>
                <ActivityCard title="Latest service jobs">
                    {data.serviceJobs.length ? data.serviceJobs.map((job) => <ActivityRow amount={job.grandTotal} date={job.promisedDeliveryDateTime} key={job.id} label={job.jobCardNumber} status={job.status} subtitle={`${job.customerName ?? 'Customer'} / ${job.registrationNumber ?? 'Vehicle'}`} to={`/vehicle-service/jobs/${job.id}`} />) : <EmptyState title="No recent service jobs" />}
                </ActivityCard>
                <ActivityCard title="Latest invoices">
                    {data.invoices.length ? data.invoices.map((invoice) => <ActivityRow amount={invoice.balanceDue} date={invoice.invoiceDate} key={invoice.id} label={invoice.invoiceNumber} status={invoice.status} subtitle={invoice.documentType.replaceAll('_', ' ')} to={`/invoices/${invoice.id}`} />) : <EmptyState title="No recent invoices" />}
                </ActivityCard>
                <ActivityCard title="Latest payments">
                    {data.payments.length ? data.payments.map((payment) => <ActivityRow amount={payment.amount} date={payment.paymentDate} key={payment.id} label={payment.paymentNumber} status={payment.status} subtitle={payment.direction} to={`/payments/${payment.id}`} />) : <EmptyState title="No recent payments" />}
                </ActivityCard>
            </div>
        </div>
    );
}

function QuickActions() {
    return (
        <>
            <PrimaryLink to="/purchase/orders/new">New PO</PrimaryLink>
            <Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50" to="/purchase/grns/new">New GRN</Link>
            <Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50" to="/vehicle-service/jobs/new">New Job</Link>
            <Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50" to="/payments/new">New Payment</Link>
        </>
    );
}

function MetricPanel({ children, title }: { children: ReactNode; title: string }) {
    return <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40"><h2 className="font-bold text-slate-950">{title}</h2><div className="mt-4 space-y-3">{children}</div></section>;
}

function Metric({ label, to, value }: { label: string; to?: string; value: ReactNode }) {
    const content = <><span className="text-sm text-slate-500">{label}</span><span className="text-sm font-bold text-slate-950">{value}</span></>;
    if (!to) return <div className="flex justify-between gap-4 rounded-lg bg-slate-50 px-3 py-2">{content}</div>;
    return <Link className="flex justify-between gap-4 rounded-lg bg-slate-50 px-3 py-2 transition hover:bg-blue-50" to={to}>{content}</Link>;
}

function ActivityCard({ children, title }: { children: ReactNode; title: string }) {
    return <TableCard><div className="border-b border-slate-100 px-5 py-4"><h2 className="font-bold text-slate-950">{title}</h2></div><div className="divide-y divide-slate-100">{children}</div></TableCard>;
}

function ActivityRow({ amount, date, label, status, subtitle, to }: { amount?: string; date?: string | null; label: string; status: string; subtitle?: string; to: string }) {
    return (
        <Link className="grid gap-3 px-5 py-4 transition hover:bg-slate-50 sm:grid-cols-[1fr_120px_130px_120px]" to={to}>
            <div><p className="font-semibold text-slate-900">{label}</p>{subtitle ? <p className="mt-1 text-xs capitalize text-slate-500">{subtitle}</p> : null}</div>
            <div className="text-sm text-slate-500"><DateDisplay value={date} /></div>
            <div className="font-semibold text-slate-900"><MoneyDisplay value={amount} /></div>
            <div><StatusBadge value={status} /></div>
        </Link>
    );
}
