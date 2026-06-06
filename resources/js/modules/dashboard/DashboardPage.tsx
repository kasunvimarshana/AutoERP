import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';

const modules = [
    ['/suppliers', 'Suppliers', 'Supplier profiles and relation-aware onboarding'],
    ['/uoms', 'UOM', 'Generic units, categories, and conversion factors'],
    ['/uom-convert', 'UOM Convert', 'Backend-powered quantity conversion'],
    ['/items', 'Items', 'Catalog, units, variants, pricing, and codes'],
    ['/inventory', 'Inventory', 'Stock balances and availability'],
    ['/purchase/orders', 'Purchase', 'Purchase orders and receipts'],
    ['/invoices', 'Invoices', 'Invoice balances, sources, and adjustments'],
    ['/payments', 'Payments', 'Allocations and unapplied balances'],
    ['/finance/accounts', 'Finance', 'Chart of accounts and ledger activity'],
];

export default function DashboardPage() {
    return (
        <>
            <ContentHeader title="Dashboard" description="A focused launchpad for the Phase 1 backend modules." />
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {modules.map(([href, title, description]) => (
                    <Link key={href} to={href}>
                        <Panel className="h-full transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md">
                            <h2 className="font-semibold text-slate-900">{title}</h2>
                            <p className="mt-2 text-sm text-slate-500">{description}</p>
                        </Panel>
                    </Link>
                ))}
            </div>
        </>
    );
}
