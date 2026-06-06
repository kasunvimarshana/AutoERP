import { useState, type ReactNode } from 'react';
import { Tabs } from '@/shared/components/Tabs';

export function PurchaseDocumentTabs<T extends string>({ tabs, panels, initial }: {
    tabs: Array<{ id: T; label: string }>;
    panels: Record<T, ReactNode>;
    initial: T;
}) {
    const [active, setActive] = useState<T>(initial);
    return (
        <>
            <Tabs tabs={tabs} active={active} onChange={setActive} />
            <div className="p-5">{panels[active]}</div>
        </>
    );
}
