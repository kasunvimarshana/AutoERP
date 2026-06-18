import { Link } from 'react-router-dom';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';

const workflowWidgets = [
    {
        title: 'Purchase',
        focus: 'Order to payment',
        items: [
            ['Pending purchase orders', '/purchase/orders?status=draft'],
            ['Goods receipts to post', '/purchase/goods-receipts'],
            ['Supplier invoices to create', '/purchase/invoices/create'],
            ['Supplier payments to create', '/purchase/payments/create'],
        ],
    },
    {
        title: 'Vehicle service',
        focus: 'Arrival to delivery',
        items: [
            ['Vehicles waiting inspection', '/vehicle-service/jobs?status=draft'],
            ['Work in progress', '/vehicle-service/jobs?status=in_progress'],
            ['Jobs ready for invoice', '/vehicle-service/jobs?status=completed'],
            ['Payments to collect', '/vehicle-service/jobs?status=invoiced'],
        ],
    },
    {
        title: 'Finance',
        focus: 'Invoice to settlement',
        items: [
            ['Customer invoices', '/invoices'],
            ['Payment allocations', '/payments'],
            ['Chart of accounts', '/finance/accounts'],
            ['Journal review', '/finance/journals'],
            ['General ledger', '/finance/ledger'],
            ['Trial balance', '/finance/trial-balance'],
        ],
    },
];

const taskCenter = [
    ['Approve purchase order', '/purchase/orders?status=draft', 'Purchasing'],
    ['Receive supplier goods', '/purchase/goods-receipts/create', 'Warehouse'],
    ['Complete service inspection', '/vehicle-service/jobs?status=draft', 'Workshop'],
    ['Generate service invoice', '/vehicle-service/jobs?status=completed', 'Service advisor'],
    ['Collect pending payment', '/vehicle-service/jobs?status=invoiced', 'Finance'],
    ['Review unpaid invoices', '/invoices', 'Finance'],
    ['Check vehicle documents', '/vehicles', 'Administration'],
    ['Review employee assignments', '/hr/employees', 'Workshop'],
];

const masterShortcuts = [
    ['Customer arrived', '/customers/create'],
    ['Vehicle inspected', '/vehicle-service/jobs/create'],
    ['Supplier onboarding', '/suppliers/create'],
    ['Item setup', '/items/create'],
];

export default function DashboardPage() {
    return (
        <>
            <ContentHeader
                title="Task center"
                description="Start from the work to be done, then move into the module only when the workflow needs it."
                actions={<LinkButton to="/vehicle-service/jobs/create">New service job</LinkButton>}
            />

            <div className="grid gap-5 xl:grid-cols-[1fr_22rem]">
                <div className="space-y-5">
                    <div className="grid gap-4 lg:grid-cols-3">
                        {workflowWidgets.map((widget) => (
                            <Panel key={widget.title} className="h-full rounded-lg">
                                <div className="mb-4">
                                    <h2 className="text-base font-semibold text-slate-900">{widget.title}</h2>
                                    <p className="mt-1 text-sm text-slate-500">{widget.focus}</p>
                                </div>
                                <div className="space-y-2">
                                    {widget.items.map(([label, href]) => (
                                        <Link key={label} to={href} className="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-800">
                                            <span>{label}</span>
                                            <span aria-hidden="true">&gt;</span>
                                        </Link>
                                    ))}
                                </div>
                            </Panel>
                        ))}
                    </div>

                    <Panel title="Actionable work" className="rounded-lg">
                        <div className="grid gap-3 md:grid-cols-2">
                            {taskCenter.map(([label, href, owner]) => (
                                <Link key={`${owner}-${label}`} to={href} className="rounded-lg border border-slate-200 p-4 transition hover:border-sky-300 hover:bg-sky-50">
                                    <p className="text-sm font-semibold text-slate-900">{label}</p>
                                    <p className="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">{owner}</p>
                                </Link>
                            ))}
                        </div>
                    </Panel>
                </div>

                <aside className="space-y-5">
                    <Panel title="Quick starts" className="rounded-lg">
                        <div className="space-y-2">
                            {masterShortcuts.map(([label, href]) => (
                                <Link key={label} to={href} className="block rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-800">
                                    {label}
                                </Link>
                            ))}
                        </div>
                    </Panel>

                    <Panel title="Workflow map" className="rounded-lg">
                        <ol className="space-y-3 text-sm text-slate-700">
                            {['Customer arrived', 'Vehicle inspected', 'Work completed', 'Invoice generated', 'Payment received'].map((step, index) => (
                                <li key={step} className="flex gap-3">
                                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">{index + 1}</span>
                                    <span className="pt-0.5">{step}</span>
                                </li>
                            ))}
                        </ol>
                    </Panel>
                </aside>
            </div>
        </>
    );
}
