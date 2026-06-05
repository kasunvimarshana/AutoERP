import { useEffect, useState } from 'react';
import { Button } from '../../../shared/components/ui/Button';
import { rentalSettings } from '../mock/vehicleRentalMock';
import { VehicleRentalPageHeader, VehicleRentalSettingsForm } from '../components/VehicleRentalComponents';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalSettings } from '../types/vehicleRental.types';

export function VehicleRentalSettingsPage() {
    const [settings, setSettings] = useState<VehicleRentalSettings>(rentalSettings);

    useEffect(() => {
        vehicleRentalApi.settings.get().then((response) => setSettings(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<><Button variant="secondary">Initialize Defaults</Button><Button variant="blue">Save Settings</Button></>}
                subtitle="Rental module settings for sequences, document definitions, rate defaults, provider payable behavior, and workflow flags."
                title="Vehicle Rental Settings"
            />
            <VehicleRentalSettingsForm settings={settings} />
        </div>
    );
}
