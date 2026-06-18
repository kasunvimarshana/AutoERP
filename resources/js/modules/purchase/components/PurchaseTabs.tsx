import { useId, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';

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
    const [searchParams, setSearchParams] = useSearchParams();
    const generatedId = useId();
    const refs = useRef(new Map<string, HTMLButtonElement>());
    const currentTab = tabs.some((tab) => tab.id === activeTab) ? activeTab : tabs[0]?.id;
    const setTab = (tabId: string, focus = false) => {
        const next = new URLSearchParams(searchParams);
        next.set('tab', tabId);
        setSearchParams(next);
        onChange?.(tabId);
        if (focus) window.requestAnimationFrame(() => refs.current.get(tabId)?.focus());
    };

    return (
        <div className="overflow-x-auto border-b border-slate-200">
            <div className="flex min-w-max gap-1" role="tablist" aria-label="Purchase document sections">
            {tabs.map((tab) => {
                const selected = tab.id === currentTab;
                const label = `${tab.label}${tab.count !== undefined ? ` (${tab.count})` : ''}`;

                return (
                    <button
                        key={tab.id}
                        ref={(element) => {
                            if (element) refs.current.set(tab.id, element);
                            else refs.current.delete(tab.id);
                        }}
                        id={`${generatedId}-${tab.id}-tab`}
                        type="button"
                        role="tab"
                        aria-selected={selected}
                        tabIndex={selected ? 0 : -1}
                        onClick={() => setTab(tab.id)}
                        onKeyDown={(event) => {
                            if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) return;
                            event.preventDefault();
                            const index = tabs.findIndex((item) => item.id === currentTab);
                            const next = event.key === 'Home'
                                ? tabs[0]
                                : event.key === 'End'
                                    ? tabs[tabs.length - 1]
                                    : tabs[(index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
                            if (next) setTab(next.id, true);
                        }}
                        className={`min-h-11 whitespace-nowrap border-b-2 px-3 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 ${selected ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900'} ${tab.error ? 'text-rose-700' : ''}`}
                    >
                        {label}
                    </button>
                );
            })}
            </div>
        </div>
    );
}
