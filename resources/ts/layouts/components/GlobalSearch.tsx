import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { StatusBadge } from '../../shared/components/business/StatusBadge';

const searchIndex = [
    { category: 'Customer', path: '/customers/cus-001', status: 'Active', subtitle: 'Northline Logistics', title: 'CUS-001' },
    { category: 'Supplier', path: '/suppliers/sup-001', status: 'Active', subtitle: 'Parts and external services', title: 'SUP-001' },
    { category: 'Item', path: '/items/item-001', status: 'Active', subtitle: 'Oil Filter | stock item', title: 'ITM-001' },
    { category: 'Vehicle Service', path: '/vehicle-service/job-cards/JC-1001', status: 'In Progress', subtitle: 'Toyota HiAce service job', title: 'JC-1001' },
    { category: 'Purchase', path: '/purchase/invoices', status: 'Mocked', subtitle: 'Supplier invoice previews', title: 'Supplier Invoices' },
    { category: 'Sales', path: '/sales/payments', status: 'Mocked', subtitle: 'Customer payment allocations', title: 'Customer Payments' },
    { category: 'Vehicle Rental', path: '/vehicle-rental/running-charts', status: 'Mocked', subtitle: 'Running chart billing previews', title: 'Running Charts' },
];

export function GlobalSearch() {
    const [query, setQuery] = useState('');
    const [focused, setFocused] = useState(false);
    const normalized = query.trim().toLowerCase();
    const results = useMemo(
        () =>
            searchIndex.filter((item) =>
                [item.category, item.subtitle, item.title].some((value) => value.toLowerCase().includes(normalized)),
            ),
        [normalized],
    );
    const visibleResults = normalized.length ? results : searchIndex.slice(0, 5);
    const showPanel = focused || normalized.length > 0;

    return (
        <div className="relative w-full max-w-2xl">
            <label className="relative block">
                <span className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">/</span>
                <input
                    className="h-11 w-full rounded-full border border-slate-200 bg-slate-50 px-10 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:bg-white focus:ring-4 focus:ring-slate-100"
                    onBlur={() => window.setTimeout(() => setFocused(false), 120)}
                    onChange={(event) => setQuery(event.target.value)}
                    onFocus={() => setFocused(true)}
                    placeholder="Search customers, suppliers, items, vehicles, job cards, invoices, payments..."
                    type="search"
                    value={query}
                />
            </label>
            {showPanel ? (
                <div className="absolute left-0 right-0 top-12 z-50 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div className="border-b border-slate-100 px-4 py-3">
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">ERP-wide search</p>
                        <p className="mt-1 text-xs text-slate-500">Mock indexed results. Backend search integration comes later.</p>
                    </div>
                    <div className="max-h-80 overflow-y-auto p-2">
                        {visibleResults.length ? (
                            visibleResults.map((item) => (
                                <Link
                                    className="flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 transition hover:bg-slate-50"
                                    key={`${item.category}-${item.title}`}
                                    to={item.path}
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-bold text-slate-900">{item.title}</p>
                                        <p className="truncate text-xs text-slate-500">{item.category} - {item.subtitle}</p>
                                    </div>
                                    <StatusBadge status={item.status} />
                                </Link>
                            ))
                        ) : (
                            <div className="px-3 py-8 text-center text-sm text-slate-500">No mock results found.</div>
                        )}
                    </div>
                </div>
            ) : null}
        </div>
    );
}
