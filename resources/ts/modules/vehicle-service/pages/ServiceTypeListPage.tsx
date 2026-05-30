import { useEffect, useState } from 'react';
import { Button } from '../../../shared/components/ui/Button';
import { ServiceTypeForm, ServiceTypeTable, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceType } from '../types/vehicleService.types';

export function ServiceTypeListPage() {
    const [rows, setRows] = useState<VehicleServiceType[]>([]);

    useEffect(() => {
        vehicleServiceApi.serviceTypes.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Button variant="blue">Create Service Type</Button>}
                subtitle="Service types configure workshop categories and workflow defaults without becoming invoice or sales logic."
                title="Service Types"
            />
            <ServiceTypeForm />
            <ServiceTypeTable rows={rows} />
        </div>
    );
}
