import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import {
    GrnTable,
    PurchaseDashboardCards,
    PurchaseInvoiceTable,
    PurchaseOrderTable,
} from '../components/PurchaseComponents';
import { purchaseApi } from '../services/purchaseApi';
import type { GoodsReceivedNote, PurchaseDashboardMetric, PurchaseInvoice, PurchaseOrder } from '../types/purchase.types';

export function PurchaseDashboardPage() {
    const [metrics, setMetrics] = useState<PurchaseDashboardMetric[]>([]);
    const [orders, setOrders] = useState<PurchaseOrder[]>([]);
    const [grns, setGrns] = useState<GoodsReceivedNote[]>([]);
    const [invoices, setInvoices] = useState<PurchaseInvoice[]>([]);

    useEffect(() => {
        let active = true;
        purchaseApi.dashboard.summary().then((response) => active && setMetrics(response.data));
        purchaseApi.orders.list({ perPage: 3 }).then((response) => active && setOrders(response.data));
        purchaseApi.grns.list({ perPage: 3 }).then((response) => active && setGrns(response.data));
        purchaseApi.invoices.list({ perPage: 3 }).then((response) => active && setInvoices(response.data.slice(0, 3)));

        return () => {
            active = false;
        };
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Link to="/purchase/orders/new"><Button>New PO</Button></Link><Link to="/purchase/invoices/new"><Button variant="blue">Supplier Invoice</Button></Link></>}
                eyebrow="Purchase"
                subtitle="Flexible purchasing workflows for PO, GRN, supplier invoices, payments, advances, returns, and refunds. Backend owns totals, tax, UOM conversion, stock effects, AP, and allocations."
                title="Purchase Dashboard"
            />
            <PurchaseDashboardCards metrics={metrics} />
            <PreviewPanel status="Workflow" title="Supported Purchase Workflows">
                <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                    {['PO -> GRN -> Supplier Invoice -> Payment', 'PO -> Supplier Invoice -> Payment', 'GRN -> Supplier Invoice -> Payment', 'Direct Supplier Invoice -> Payment', 'Purchase Return -> Supplier Refund / Credit', 'Supplier Advance -> Allocation -> Settlement'].map((workflow) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 font-semibold text-slate-700" key={workflow}>{workflow}</div>
                    ))}
                </div>
            </PreviewPanel>
            <div className="grid gap-5 xl:grid-cols-2">
                <div className="space-y-3">
                    <h2 className="text-base font-bold text-slate-950">Recent Purchase Orders</h2>
                    <PurchaseOrderTable rows={orders.slice(0, 3)} />
                </div>
                <div className="space-y-3">
                    <h2 className="text-base font-bold text-slate-950">GRNs Awaiting Invoice</h2>
                    <GrnTable rows={grns} />
                </div>
            </div>
            <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Supplier Invoices</h2>
                <PurchaseInvoiceTable rows={invoices} />
            </div>
        </div>
    );
}

export { PurchaseDashboardPage as PurchasePage };
