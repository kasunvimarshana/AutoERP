import { Link, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { Card } from '../../../components/ui/Card';
import { cn } from '../../../lib/cn';
import { useDashboardOverview } from '../hooks/useDashboardOverview';
import type { DashboardKpi } from '../types';

type PreviewState = 'default' | 'loading' | 'empty' | 'error';

function getKpiToneClass(tone: DashboardKpi['tone']) {
    if (tone === 'positive') {
        return 'bg-emerald-50 text-emerald-700';
    }

    if (tone === 'warning') {
        return 'bg-amber-50 text-amber-700';
    }

    return 'bg-stone-100 text-stone-600';
}

function DashboardLoadingState() {
    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                {Array.from({ length: 6 }).map((_, index) => (
                    <Card key={index} className="p-5">
                        <div className="animate-pulse space-y-4">
                            <div className="h-3 w-24 rounded-full bg-stone-200" />
                            <div className="h-8 w-20 rounded-full bg-stone-200" />
                            <div className="h-3 w-full rounded-full bg-stone-100" />
                        </div>
                    </Card>
                ))}
            </div>

            <div className="grid gap-4 xl:grid-cols-[1.45fr_0.95fr]">
                <Card className="p-6">
                    <div className="animate-pulse space-y-4">
                        <div className="h-4 w-40 rounded-full bg-stone-200" />
                        <div className="h-16 rounded-2xl bg-stone-100" />
                        <div className="h-16 rounded-2xl bg-stone-100" />
                        <div className="h-16 rounded-2xl bg-stone-100" />
                    </div>
                </Card>
                <Card className="p-6">
                    <div className="animate-pulse space-y-4">
                        <div className="h-4 w-32 rounded-full bg-stone-200" />
                        <div className="h-12 rounded-2xl bg-stone-100" />
                        <div className="h-12 rounded-2xl bg-stone-100" />
                        <div className="h-12 rounded-2xl bg-stone-100" />
                    </div>
                </Card>
            </div>

            <div className="grid gap-4 xl:grid-cols-[1.25fr_1fr]">
                <Card className="p-6">
                    <div className="animate-pulse space-y-4">
                        <div className="h-4 w-36 rounded-full bg-stone-200" />
                        <div className="h-52 rounded-2xl bg-stone-100" />
                    </div>
                </Card>
                <Card className="p-6">
                    <div className="animate-pulse space-y-4">
                        <div className="h-4 w-32 rounded-full bg-stone-200" />
                        <div className="h-16 rounded-2xl bg-stone-100" />
                        <div className="h-16 rounded-2xl bg-stone-100" />
                    </div>
                </Card>
            </div>
        </div>
    );
}

export function DashboardPage() {
    const [searchParams] = useSearchParams();
    const previewState = (searchParams.get('preview') ?? 'default') as PreviewState;
    const overviewQuery = useDashboardOverview(previewState);

    if (overviewQuery.isPending) {
        return <DashboardLoadingState />;
    }

    if (overviewQuery.isError) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <Card className="p-8">
                    <p className="text-xs font-medium uppercase tracking-[0.22em] text-stone-500">Dashboard</p>
                    <h2 className="mt-3 text-3xl font-semibold text-stone-950">Unable to load the dashboard shell</h2>
                    <p className="mt-4 max-w-2xl text-sm leading-6 text-stone-600">{overviewQuery.error.message}</p>
                    <div className="mt-6">
                        <Button onClick={() => void overviewQuery.refetch()} variant="secondary">
                            Retry dashboard
                        </Button>
                    </div>
                </Card>
            </div>
        );
    }

    const data = overviewQuery.data;
    const isEmpty = data.auditActivity.length === 0 && data.lowStockItems.length === 0 && data.pendingApprovals.length === 0;

    if (isEmpty) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <Card className="p-8">
                    <p className="text-xs font-medium uppercase tracking-[0.22em] text-stone-500">Dashboard</p>
                    <h2 className="mt-3 text-3xl font-semibold text-stone-950">The workspace is ready for first activity</h2>
                    <p className="mt-4 max-w-3xl text-sm leading-6 text-stone-600">
                        No dashboard signals are available yet. Once transactions, approvals, and audit activity start flowing,
                        this layout is ready to show them without structural changes.
                    </p>
                </Card>
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <Card className="overflow-hidden">
                <div className="flex flex-col gap-4 border-b border-stone-200/80 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-xs font-medium uppercase tracking-[0.22em] text-stone-500">Overview</p>
                        <h2 className="mt-2 text-2xl font-semibold text-stone-950">Operational command center</h2>
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-stone-600">
                            This dashboard is arranged for real ERP data later, but it already gives the app shell the right
                            rhythm: KPIs first, then actions, operational exceptions, and recent audit visibility.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Link to="/sales/orders">
                            <Button variant="secondary">Sales queue</Button>
                        </Link>
                        <Link to="/inventory/stock-levels">
                            <Button>Inventory alerts</Button>
                        </Link>
                    </div>
                </div>

                <div className="grid gap-4 px-6 py-6 md:grid-cols-2 xl:grid-cols-6">
                    {data.kpis.map((kpi) => (
                        <div
                            key={kpi.id}
                            className="rounded-2xl border border-stone-200/80 bg-stone-50/60 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.6)]"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm font-medium text-stone-600">{kpi.label}</p>
                                <span className={cn('rounded-full px-2 py-1 text-[11px] font-semibold', getKpiToneClass(kpi.tone))}>
                                    {kpi.delta}
                                </span>
                            </div>
                            <p className="mt-4 text-3xl font-semibold text-stone-950">{kpi.value}</p>
                            <p className="mt-2 text-sm leading-6 text-stone-600">{kpi.detail}</p>
                        </div>
                    ))}
                </div>
            </Card>

            <div className="grid gap-4 xl:grid-cols-[1.45fr_0.95fr]">
                <Card className="p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h3 className="text-lg font-semibold text-stone-950">Recent audit activity</h3>
                            <p className="mt-1 text-sm leading-6 text-stone-600">Latest sensitive actions and operational events.</p>
                        </div>
                        <Link className="text-sm font-medium text-stone-700 hover:text-stone-950" to="/audit-logs/activity">
                            View all
                        </Link>
                    </div>

                    <div className="mt-6 space-y-3">
                        {data.auditActivity.map((activity) => (
                            <div key={activity.id} className="rounded-2xl border border-stone-200/80 bg-stone-50/70 px-4 py-4">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium text-stone-950">
                                            {activity.actor} <span className="font-normal text-stone-600">{activity.action}</span>{' '}
                                            {activity.target}
                                        </p>
                                        <p className="text-xs uppercase tracking-[0.14em] text-stone-500">{activity.tenant}</p>
                                    </div>
                                    <span className="shrink-0 text-xs text-stone-500">{activity.timestamp}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </Card>

                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-stone-950">Quick actions</h3>
                    <p className="mt-1 text-sm leading-6 text-stone-600">Fast links into the next operational step.</p>

                    <div className="mt-6 space-y-3">
                        {data.quickActions.map((action) => (
                            <Link
                                key={action.id}
                                className="block rounded-2xl border border-stone-200/80 bg-stone-50/70 px-4 py-4 transition hover:border-stone-300 hover:bg-white"
                                to={action.path}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-medium text-stone-950">{action.label}</p>
                                        <p className="mt-1 text-sm leading-6 text-stone-600">{action.description}</p>
                                    </div>
                                    <svg
                                        aria-hidden="true"
                                        className="mt-1 h-4 w-4 shrink-0 text-stone-500"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path d="M7 17 17 7" />
                                        <path d="M7 7h10v10" />
                                    </svg>
                                </div>
                            </Link>
                        ))}
                    </div>
                </Card>
            </div>

            <div className="grid gap-4 xl:grid-cols-[1.2fr_0.9fr]">
                <Card className="overflow-hidden">
                    <div className="border-b border-stone-200/80 px-6 py-5">
                        <h3 className="text-lg font-semibold text-stone-950">Low stock watchlist</h3>
                        <p className="mt-1 text-sm leading-6 text-stone-600">Inventory exceptions arranged for replenishment review.</p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="bg-stone-50 text-stone-500">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Product</th>
                                    <th className="px-6 py-3 font-medium">Warehouse</th>
                                    <th className="px-6 py-3 font-medium">Available</th>
                                    <th className="px-6 py-3 font-medium">Reorder Point</th>
                                </tr>
                            </thead>
                            <tbody>
                                {data.lowStockItems.map((item) => (
                                    <tr key={item.id} className="border-t border-stone-200/80">
                                        <td className="px-6 py-4">
                                            <div>
                                                <p className="font-medium text-stone-950">{item.product}</p>
                                                <p className="mt-1 font-mono text-xs text-stone-500">{item.sku}</p>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">{item.warehouse}</td>
                                        <td className="px-6 py-4">
                                            <span className="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                                                {item.availableQty}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">{item.reorderPoint}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-stone-950">Pending approvals</h3>
                    <p className="mt-1 text-sm leading-6 text-stone-600">Approval workload waiting for action.</p>

                    <div className="mt-6 space-y-3">
                        {data.pendingApprovals.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50 px-4 py-8 text-center text-sm text-stone-500">
                                No approvals are waiting right now.
                            </div>
                        ) : (
                            data.pendingApprovals.map((approval) => (
                                <div key={approval.id} className="rounded-2xl border border-stone-200/80 bg-stone-50/70 px-4 py-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-medium text-stone-950">{approval.title}</p>
                                            <p className="mt-1 text-sm text-stone-600">{approval.owner}</p>
                                        </div>
                                        <span className="rounded-full bg-stone-950 px-2 py-1 text-[11px] font-semibold text-white">
                                            {approval.amount}
                                        </span>
                                    </div>
                                    <p className="mt-3 text-xs uppercase tracking-[0.14em] text-stone-500">{approval.dueLabel}</p>
                                </div>
                            ))
                        )}
                    </div>
                </Card>
            </div>
        </div>
    );
}
