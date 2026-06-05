import { useEffect, useState } from 'react';
import { Button } from '../../../shared/components/ui/Button';
import { VehicleServicePageHeader, VehicleServiceSettingsForm } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceSettings } from '../types/vehicleService.types';

export function VehicleServiceSettingsPage() {
    const [settings, setSettings] = useState<VehicleServiceSettings>();

    useEffect(() => {
        vehicleServiceApi.settings.get().then((response) => setSettings(response.data));
    }, []);

    async function initialize(): Promise<void> {
        await vehicleServiceApi.settings.initialize();
        const response = await vehicleServiceApi.settings.get();
        setSettings(response.data);
    }

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Button onClick={initialize} variant="secondary">Initialize Defaults</Button>}
                subtitle="Module settings for workshop defaults, sequences, stock timing, invoice definition, and integration behavior."
                title="Vehicle Service Settings"
            />
            {settings ? <VehicleServiceSettingsForm settings={settings} /> : null}
        </div>
    );
}
