export interface TabItem<T extends string> {
    id: T;
    label: string;
}

export function Tabs<T extends string>({ tabs, active, onChange }: {
    tabs: TabItem<T>[];
    active: T;
    onChange: (tab: T) => void;
}) {
    return (
        <div className="overflow-x-auto border-b border-slate-200">
            <div className="flex min-w-max gap-1" role="tablist">
                {tabs.map((tab) => (
                    <button
                        key={tab.id}
                        type="button"
                        role="tab"
                        aria-selected={active === tab.id}
                        tabIndex={active === tab.id ? 0 : -1}
                        onClick={() => onChange(tab.id)}
                        onKeyDown={(event) => {
                            const index = tabs.findIndex((item) => item.id === active);
                            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
                            event.preventDefault();
                            const direction = event.key === 'ArrowRight' ? 1 : -1;
                            const next = tabs[(index + direction + tabs.length) % tabs.length];
                            onChange(next.id);
                        }}
                        className={`border-b-2 px-4 py-3 text-sm font-medium transition ${active === tab.id ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>
        </div>
    );
}
