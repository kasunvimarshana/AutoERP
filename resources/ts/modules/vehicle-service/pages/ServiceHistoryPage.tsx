import { useEffect, useState } from 'react';
import { VehicleServiceActivityTimeline, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceAuditEntry } from '../types/vehicleService.types';

export function ServiceHistoryPage() {
    const [rows, setRows] = useState<VehicleServiceAuditEntry[]>([]);

    useEffect(() => {
        vehicleServiceApi.history.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                subtitle="Service history shows backend job-card workflow events, documents, payments, inventory, and finance actions."
                title="Service History"
            />
            <VehicleServiceActivityTimeline rows={rows} />
        </div>
    );
}
