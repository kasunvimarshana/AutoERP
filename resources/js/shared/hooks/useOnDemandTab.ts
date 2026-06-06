import { useState } from 'react';

export function useOnDemandTab<T extends string>(initialTab: T) {
    const [activeTab, setActiveTab] = useState<T>(initialTab);
    const [openedTabs, setOpenedTabs] = useState<Set<T>>(() => new Set([initialTab]));

    const openTab = (tab: T) => {
        setActiveTab(tab);
        setOpenedTabs((current) => {
            const next = new Set(current);
            next.add(tab);
            return next;
        });
    };

    return { activeTab, openedTabs, openTab };
}
