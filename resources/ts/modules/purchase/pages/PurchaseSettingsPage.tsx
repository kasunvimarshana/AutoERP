import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PurchaseSettingsForm } from '../components/PurchaseComponents';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseSettings } from '../types/purchase.types';

export function PurchaseSettingsPage() {
    const [settings, setSettings] = useState<PurchaseSettings>();

    useEffect(() => {
        purchaseApi.settings.get().then((response) => setSettings(response.data));
    }, []);

    if (!settings) {
        return <EmptyState description="Loading purchase settings..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Purchase"
                subtitle="Module settings for defaults, sequence references, invoice type, workflow flexibility, stock timing, and invoice matching."
                title="Purchase Settings"
            />
            <PurchaseSettingsForm settings={settings} />
        </div>
    );
}
