import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { paymentDashboardMetrics } from '../mock/paymentMock';

export function PaymentDashboardPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/payments/payments/new"><Button>New Payment</Button></Link>}
                eyebrow="Core Payments"
                subtitle="Reusable payment console for receipts, supplier payments, advances, refunds, write-offs, checks, and cash registers. Backend owns balances, allocations, settlement, and postings."
                title="Payments"
            />
            <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                {paymentDashboardMetrics.map((metric) => (
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
            </div>
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
