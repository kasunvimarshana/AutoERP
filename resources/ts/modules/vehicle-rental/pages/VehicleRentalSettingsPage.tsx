import { useEffect, useState } from 'react';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { VehicleRentalPageHeader, VehicleRentalSettingsForm } from '../components/VehicleRentalComponents';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalSettings } from '../types/vehicleRental.types';

export function VehicleRentalSettingsPage() {
    const [settings, setSettings] = useState<VehicleRentalSettings>();
    const [error, setError] = useState<string>();

    function load(): void {
        setError(undefined);
        vehicleRentalApi.settings.get().then((response) => setSettings(response.data)).catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load rental settings.'));
    }

    useEffect(load, []);

    async function initialize(): Promise<void> {
        try {
            const response = await vehicleRentalApi.settings.initialize();
            setSettings(response.data);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to initialize settings.');
        }
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<><Button onClick={initialize} variant="secondary">Initialize Defaults</Button><Button onClick={load} variant="blue">Reload Settings</Button></>}
                subtitle="Rental module settings for sequences, document definitions, rate defaults, provider payable behavior, and workflow flags."
                title="Vehicle Rental Settings"
            />
            {error ? <EmptyState description={error} title="Settings unavailable" /> : null}
            {settings ? <VehicleRentalSettingsForm settings={settings} /> : !error ? <EmptyState description="Loading Vehicle Rental settings from backend..." title="Loading settings" /> : null}
        </div>
    );
}
