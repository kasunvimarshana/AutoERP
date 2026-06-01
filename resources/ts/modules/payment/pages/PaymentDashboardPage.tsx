import { useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { paymentApi } from '../services/paymentApi';

export function PaymentDashboardPage() {
    const [metrics, setMetrics] = useState<Array<{ label: string; tone: string; value: string }>>([]);
    const [error, setError] = useState('');
    const [isLoaded, setIsLoaded] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    async function loadDashboardData(): Promise<void> {
        if (isLoading) return;

        setIsLoading(true);
        setError('');

        try {
            const response = await paymentApi.listDashboardMetrics();
            setMetrics(response.data);
            setIsLoaded(true);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to load Payment dashboard.');
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/payments/payments/new"><Button>New Payment</Button></Link>}
                eyebrow="Core Payments"
                subtitle="Reusable payment console for receipts, supplier payments, advances, refunds, write-offs, checks, and cash registers. Backend owns balances, allocations, settlement, and postings."
                title="Payments"
            />
            <div className="flex justify-end">
                <Button disabled={isLoading} onClick={() => void loadDashboardData()} type="button" variant="secondary">{isLoaded ? 'Refresh Dashboard Data' : 'Load Dashboard Data'}</Button>
            </div>
            {error ? <EmptyState description={error} title="Payment dashboard unavailable" /> : null}
            {!isLoaded && !error ? <EmptyState description="Payment metrics load only when requested." title="Dashboard data not loaded" /> : null}
            {isLoaded ? <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                {metrics.map((metric) => (
                    <Card className="p-4" key={metric.label}>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{metric.label}</p>
                                <p className="mt-3 text-2xl font-bold text-slate-950">{metric.value}</p>
                            </div>
                            <StatusBadge status={metric.tone} />
                        </div>
                    </Card>
                ))}
            </div> : null}
            <div className="grid gap-4 md:grid-cols-4">
                {[
                    ['Create payment', '/payments/payments/new'],
                    ['Allocate payment', '/payments/allocations'],
                    ['Create advance', '/payments/advances'],
                    ['Create refund', '/payments/refunds'],
                ].map(([label, path]) => (
                    <Link className="rounded-lg border border-slate-200 bg-white p-5 text-sm font-bold text-slate-900 shadow-sm hover:border-slate-300" key={label} to={path}>
                        {label}
                    </Link>
                ))}
            </div>
        </div>
    );
}
