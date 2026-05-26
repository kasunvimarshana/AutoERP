type RelatedDocumentsTab = {
    id: string;
    label: string;
};

type RelatedDocumentsTabsProps = {
    tabs: RelatedDocumentsTab[];
    activeTab: string;
    onChange: (tabId: string) => void;
};

export function RelatedDocumentsTabs({ tabs, activeTab, onChange }: RelatedDocumentsTabsProps) {
    return (
        <div className="flex flex-wrap gap-2">
            {tabs.map((tab) => (
                <button
                    key={tab.id}
                    className={
                        activeTab === tab.id
                            ? 'rounded-2xl border border-stone-950 bg-stone-950 px-4 py-2 text-sm font-medium text-white'
                            : 'rounded-2xl border border-stone-200 bg-white px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-50 hover:text-stone-900'
                    }
                    onClick={() => onChange(tab.id)}
                    type="button"
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
}
