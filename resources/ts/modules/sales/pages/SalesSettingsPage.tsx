import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { SalesSettingsForm } from '../components/SalesComponents';
import { salesApi } from '../services/salesApi';
import type { SalesSettings } from '../types/sales.types';

export function SalesSettingsPage() {
    const [settings, setSettings] = useState<SalesSettings>();

    useEffect(() => {
        salesApi.settings.get().then((response) => setSettings(response.data));
    }, []);

    if (!settings) {
        return <EmptyState description="Loading sales settings..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Sales"
                subtitle="Module settings for defaults, sequence references, document definition, credit behavior, workflow flexibility, stock timing, and invoice matching."
                title="Sales Settings"
            />
            <SalesSettingsForm settings={settings} />
        </div>
    );
}
