import { useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { StatusBadge } from '../../shared/components/business/StatusBadge';

const searchIndex = [
    { category: 'Customer', path: '/customers', status: 'Module', subtitle: 'Customer list and profiles', title: 'Customers' },
    { category: 'Supplier', path: '/suppliers', status: 'Module', subtitle: 'Supplier list and profiles', title: 'Suppliers' },
    { category: 'Item', path: '/items', status: 'Module', subtitle: 'Item master data', title: 'Items' },
    { category: 'Purchase', path: '/purchase/invoices', status: 'Module', subtitle: 'Supplier invoice workflow', title: 'Supplier Invoices' },
    { category: 'Sales', path: '/sales/payments', status: 'Module', subtitle: 'Customer payment allocations', title: 'Customer Payments' },
    { category: 'Vehicle Service', path: '/vehicle-service/job-cards', status: 'Module', subtitle: 'Service job cards', title: 'Job Cards' },
    { category: 'Vehicle Rental', path: '/vehicle-rental/running-charts', status: 'Module', subtitle: 'Running chart workflows', title: 'Running Charts' },
];

export function GlobalSearch() {
    const [query, setQuery] = useState('');
    const [focused, setFocused] = useState(false);
    const blurTimeoutRef = useRef<number | null>(null);
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

    useEffect(() => () => {
        if (blurTimeoutRef.current !== null) {
            window.clearTimeout(blurTimeoutRef.current);
        }
    }, []);

    function closeAfterBlur(): void {
        if (blurTimeoutRef.current !== null) {
            window.clearTimeout(blurTimeoutRef.current);
        }

        blurTimeoutRef.current = window.setTimeout(() => {
            setFocused(false);
            blurTimeoutRef.current = null;
        }, 120);
    }

    function openSearch(): void {
        if (blurTimeoutRef.current !== null) {
            window.clearTimeout(blurTimeoutRef.current);
            blurTimeoutRef.current = null;
        }
        setFocused(true);
    }

    return (
        <div className="relative w-full max-w-2xl">
            <label className="relative block">
                <span className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">/</span>
                <input
                    className="h-11 w-full rounded-full border border-slate-200 bg-slate-50 px-10 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:bg-white focus:ring-4 focus:ring-slate-100"
                    onBlur={closeAfterBlur}
                    onChange={(event) => setQuery(event.target.value)}
                    onFocus={openSearch}
                    placeholder="Search customers, suppliers, items, vehicles, job cards, invoices, payments..."
                    type="search"
                    value={query}
                />
            </label>
            {showPanel ? (
                <div className="absolute left-0 right-0 top-12 z-50 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div className="border-b border-slate-100 px-4 py-3">
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">ERP-wide search</p>
                        <p className="mt-1 text-xs text-slate-500">Module quick links. Backend record search is not enabled here.</p>
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
                            <div className="px-3 py-8 text-center text-sm text-slate-500">No results found.</div>
                        )}
                    </div>
                </div>
            ) : null}
        </div>
    );
}
