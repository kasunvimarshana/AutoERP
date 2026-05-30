import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { GdnTable, SalesDashboardCards, SalesInvoiceTable, SalesOrderTable } from '../components/SalesComponents';
import { gdns, salesInvoices } from '../mock/salesMock';
import { salesApi } from '../services/salesApi';
import type { SalesDashboardMetric, SalesOrder } from '../types/sales.types';

export function SalesDashboardPage() {
    const [metrics, setMetrics] = useState<SalesDashboardMetric[]>([]);
    const [orders, setOrders] = useState<SalesOrder[]>([]);

    useEffect(() => {
        salesApi.dashboard.summary().then((response) => setMetrics(response.data));
        salesApi.orders.list().then((response) => setOrders(response.data));
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
                    <GdnTable rows={gdns} />
                </div>
            </div>
            <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Customer Invoices</h2>
                <SalesInvoiceTable rows={salesInvoices} />
            </div>
        </div>
    );
}

export { SalesDashboardPage as SalesPage };
