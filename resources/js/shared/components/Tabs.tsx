import { useId, useRef, type ReactNode } from 'react';

export interface TabItem<T extends string> {
    id: T;
    label: ReactNode;
    badge?: number | ReactNode;
}

export function Tabs<T extends string>({ tabs, active, onChange, id }: {
    tabs: TabItem<T>[];
    active: T;
    onChange: (tab: T) => void;
    id?: string;
}) {
    const generatedId = useId();
    const baseId = id ?? generatedId;
    const refs = useRef(new Map<T, HTMLButtonElement>());

    const selectTab = (tabId: T, focus = false) => {
        onChange(tabId);
        if (focus) window.requestAnimationFrame(() => refs.current.get(tabId)?.focus());
    };

    return (
        <div className="overflow-x-auto border-b border-slate-200">
            <div className="flex min-w-max gap-1" role="tablist" aria-label="Page sections">
                {tabs.map((tab) => (
                    <button
                        key={tab.id}
                        ref={(element) => {
                            if (element) refs.current.set(tab.id, element);
                            else refs.current.delete(tab.id);
                        }}
                        id={`${baseId}-${tab.id}-tab`}
                        type="button"
                        role="tab"
                        aria-selected={active === tab.id}
                        aria-controls={id ? `${baseId}-${tab.id}-panel` : undefined}
                        tabIndex={active === tab.id ? 0 : -1}
                        onClick={() => selectTab(tab.id)}
                        onKeyDown={(event) => {
                            const index = tabs.findIndex((item) => item.id === active);
                            if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) return;
                            event.preventDefault();
                            const next = event.key === 'Home'
                                ? tabs[0]
                                : event.key === 'End'
                                    ? tabs[tabs.length - 1]
                                    : tabs[(index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
                            selectTab(next.id, true);
                        }}
                        className={`inline-flex min-h-11 items-center border-b-2 px-4 py-3 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 ${active === tab.id ? 'border-blue-600 text-blue-700 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'}`}
                    >
                        <span>{tab.label}</span>
                        {tab.badge !== undefined && tab.badge !== null && (
                            <span className={`ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${active === tab.id ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600'}`}>
                                {tab.badge}
                            </span>
                        )}
                    </button>
                ))}
            </div>
        </div>
    );
}

export function TabPanel<T extends string>({ tabsId, tabId, active, children, keepMounted = false }: {
    tabsId: string;
    tabId: T;
    active: T;
    children: ReactNode;
    keepMounted?: boolean;
}) {
    const isActive = active === tabId;

    if (!isActive && !keepMounted) return null;

    return (
        <div
            id={`${tabsId}-${tabId}-panel`}
            role="tabpanel"
            aria-labelledby={`${tabsId}-${tabId}-tab`}
            tabIndex={isActive ? 0 : -1}
            hidden={!isActive}
        >
            {children}
        </div>
    );
}
