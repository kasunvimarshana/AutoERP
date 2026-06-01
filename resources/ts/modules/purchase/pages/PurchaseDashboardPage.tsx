import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
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
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [recentError, setRecentError] = useState('');
    const [recentLoaded, setRecentLoaded] = useState(false);
    const [recentLoading, setRecentLoading] = useState(false);

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        purchaseApi.dashboard.summary()
            .then((metricResponse) => {
                if (!mounted) return;
                setMetrics(metricResponse.data);
            })
            .catch((caught: unknown) => {
                if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load Purchase dashboard.');
            })
            .finally(() => {
                if (mounted) setIsLoading(false);
            });

        return () => {
            mounted = false;
        };
    }, []);

    async function loadRecentDocuments(): Promise<void> {
        if (recentLoaded || recentLoading) return;

        setRecentLoading(true);
        setRecentError('');

        try {
            const [orderResponse, grnResponse, invoiceResponse] = await Promise.all([
                purchaseApi.orders.list({ perPage: 3 }),
                purchaseApi.grns.list({ perPage: 3 }),
                purchaseApi.invoices.list({ perPage: 3 }),
            ]);

            setOrders(orderResponse.data);
            setGrns(grnResponse.data);
            setInvoices(invoiceResponse.data);
            setRecentLoaded(true);
        } catch (caught) {
            setRecentError(caught instanceof Error ? caught.message : 'Unable to load recent Purchase documents.');
        } finally {
            setRecentLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Link to="/purchase/orders/new"><Button>New PO</Button></Link><Link to="/purchase/invoices/new"><Button variant="blue">Supplier Invoice</Button></Link></>}
                eyebrow="Purchase"
                subtitle="Flexible purchasing workflows for PO, GRN, supplier invoices, payments, advances, returns, and refunds. Backend owns totals, tax, UOM conversion, stock effects, AP, and allocations."
                title="Purchase Dashboard"
            />
            {error ? <EmptyState description={error} title="Purchase dashboard unavailable" /> : null}
            {!error && isLoading ? <EmptyState description="Loading real Purchase metrics..." title="Loading Purchase dashboard" /> : null}
            {!error && !isLoading && metrics.length === 0 ? <EmptyState description="No Purchase activity is available for the current tenant and organization unit." title="No Purchase data" /> : null}
            <PurchaseDashboardCards metrics={metrics} />
            <PreviewPanel status="Workflow" title="Supported Purchase Workflows">
                <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                    {['PO -> GRN -> Supplier Invoice -> Payment', 'PO -> Supplier Invoice -> Payment', 'GRN -> Supplier Invoice -> Payment', 'Direct Supplier Invoice -> Payment', 'Purchase Return -> Supplier Refund / Credit', 'Supplier Advance -> Allocation -> Settlement'].map((workflow) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 font-semibold text-slate-700" key={workflow}>{workflow}</div>
                    ))}
                </div>
            </PreviewPanel>
            <div className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="text-base font-bold text-slate-950">Recent Purchase Documents</h2>
                    <Button disabled={recentLoading} onClick={() => void loadRecentDocuments()} type="button" variant="secondary">{recentLoaded ? 'Refresh Loaded Documents' : 'Load Recent Documents'}</Button>
                </div>
                {recentError ? <EmptyState description={recentError} title="Recent documents unavailable" /> : null}
                {!recentLoaded && !recentError ? <EmptyState description="Recent orders, GRNs, and invoices load only when requested." title="Recent documents not loaded" /> : null}
                {recentLoaded ? (
                    <>
                        <div className="grid gap-5 xl:grid-cols-2">
                            <div className="space-y-3">
                                <h2 className="text-base font-bold text-slate-950">Recent Purchase Orders</h2>
                                <PurchaseOrderTable rows={orders} />
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
                    </>
                ) : null}
            </div>
        </div>
    );
}

export { PurchaseDashboardPage as PurchasePage };
