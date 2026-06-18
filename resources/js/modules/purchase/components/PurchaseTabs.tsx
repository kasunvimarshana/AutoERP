import { Link, useLocation } from 'react-router-dom';

export interface PurchaseTabItem {
    id: string;
    label: string;
    count?: number;
    error?: boolean;
}

export function PurchaseTabs({ tabs, activeTab, onChange }: {
    tabs: PurchaseTabItem[];
    activeTab: string;
    onChange?: (tab: string) => void;
}) {
    const location = useLocation();

    return (
        <nav className="flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="Purchase document sections">
            {tabs.map((tab) => {
                const params = new URLSearchParams(location.search);
                params.set('tab', tab.id);
                const selected = tab.id === activeTab;
                const label = `${tab.label}${tab.count !== undefined ? ` (${tab.count})` : ''}`;

                return (
                    <Link
                        key={tab.id}
                        to={`${location.pathname}?${params.toString()}`}
                        onClick={() => onChange?.(tab.id)}
                        className={`whitespace-nowrap border-b-2 px-3 py-2 text-sm font-semibold ${selected ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900'} ${tab.error ? 'text-rose-700' : ''}`}
                    >
                        {label}
                    </Link>
                );
            })}
        </nav>
    );
}
