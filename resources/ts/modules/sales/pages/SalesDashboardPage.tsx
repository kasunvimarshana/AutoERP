import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { GdnTable, SalesDashboardCards, SalesInvoiceTable, SalesOrderTable } from '../components/SalesComponents';
import { salesApi } from '../services/salesApi';
import type { GoodsDeliveryNote, SalesDashboardMetric, SalesInvoice, SalesOrder } from '../types/sales.types';

export function SalesDashboardPage() {
    const [metrics, setMetrics] = useState<SalesDashboardMetric[]>([]);
    const [deliveries, setDeliveries] = useState<GoodsDeliveryNote[]>([]);
    const [invoices, setInvoices] = useState<SalesInvoice[]>([]);
    const [orders, setOrders] = useState<SalesOrder[]>([]);
    const [error, setError] = useState('');

    useEffect(() => {
        Promise.all([
            salesApi.dashboard.summary(),
            salesApi.orders.list({ perPage: 3 }),
            salesApi.deliveries.list({ perPage: 3 }),
            salesApi.invoices.list({ perPage: 3 }),
        ])
            .then(([summary, orderResponse, deliveryResponse, invoiceResponse]) => {
                setMetrics(summary.data);
                setOrders(orderResponse.data);
                setDeliveries(deliveryResponse.data);
                setInvoices(invoiceResponse.data);
            })
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load Sales dashboard.'));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Link to="/sales/orders/new"><Button>New SO</Button></Link><Link to="/sales/invoices/new"><Button variant="blue">Customer Invoice</Button></Link></>}
                eyebrow="Sales"
                subtitle="Flexible sales workflows for quotations, sales orders, deliveries, customer invoices, payments, advances, returns, and refunds. Backend owns pricing, tax, UOM, credit, stock, AR, COGS, and allocations."
                title="Sales Dashboard"
            />
            <SalesDashboardCards metrics={metrics} />
            {error ? <EmptyState description={error} title="Sales dashboard unavailable" /> : null}
            <PreviewPanel status="Workflow" title="Supported Sales Workflows">
                <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                    {['Quotation -> Sales Order -> Delivery/GDN -> Customer Invoice -> Payment', 'Sales Order -> Delivery/GDN -> Customer Invoice -> Payment', 'Sales Order -> Customer Invoice -> Payment', 'Direct Customer Invoice -> Payment', 'Customer Advance -> Allocation -> Settlement', 'Sales Return -> Customer Refund / Credit'].map((workflow) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 font-semibold text-slate-700" key={workflow}>{workflow}</div>
                    ))}
                </div>
            </PreviewPanel>
            <div className="grid gap-5 xl:grid-cols-2">
                <div className="space-y-3">
                    <h2 className="text-base font-bold text-slate-950">Recent Sales Orders</h2>
                    <SalesOrderTable rows={orders.slice(0, 3)} />
                </div>
                <div className="space-y-3">
                    <h2 className="text-base font-bold text-slate-950">Deliveries Awaiting Invoice</h2>
                    <GdnTable rows={deliveries} />
                </div>
            </div>
            <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Customer Invoices</h2>
                <SalesInvoiceTable rows={invoices} />
            </div>
        </div>
    );
}

export { SalesDashboardPage as SalesPage };
