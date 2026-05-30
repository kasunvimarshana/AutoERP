import { useEffect, useState } from 'react';
import { Button } from '../../../shared/components/ui/Button';
import { vehicleServiceSettings } from '../mock/vehicleServiceMock';
import { VehicleServicePageHeader, VehicleServiceSettingsForm } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceSettings } from '../types/vehicleService.types';

export function VehicleServiceSettingsPage() {
    const [settings, setSettings] = useState<VehicleServiceSettings>(vehicleServiceSettings);

    useEffect(() => {
        vehicleServiceApi.settings.get().then((response) => setSettings(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<><Button variant="secondary">Initialize Defaults</Button><Button variant="blue">Save Settings</Button></>}
                subtitle="Module settings for workshop defaults, sequences, stock timing, document definition, and integration behavior."
                title="Vehicle Service Settings"
            />
            <VehicleServiceSettingsForm settings={settings} />
        </div>
    );
}
