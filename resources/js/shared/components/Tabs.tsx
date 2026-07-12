import { useId, useRef, type ReactNode } from 'react';

export interface TabItem<T extends string> {
    id: T;
    label: string;
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
                        className={`min-h-11 border-b-2 px-4 py-3 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 ${active === tab.id ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                    >
                        {tab.label}
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
