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
            <div className="flex min-w-max gap-1">
                {tabs.map((tab) => (
                    <button
                        key={tab.id}
                        type="button"
                        onClick={() => onChange(tab.id)}
                        className={`border-b-2 px-4 py-3 text-sm font-medium transition ${active === tab.id ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>
        </div>
    );
}
