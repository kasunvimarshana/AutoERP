import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { JobCardTable, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceJobCard } from '../types/vehicleService.types';

export function JobCardListPage() {
    const [rows, setRows] = useState<VehicleServiceJobCard[]>([]);

    useEffect(() => {
        vehicleServiceApi.jobCards.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to="/vehicle-service/job-cards/new"><Button>New Job Card</Button></Link>}
                subtitle="Job cards are workshop records. Backend owns workflow state, pricing, UOM conversion, stock effects, invoice totals, payments, and finance posting."
                title="Job Cards"
            />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px_180px]">
                    <Input placeholder="Search job card, customer, vehicle, service advisor..." />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Open', value: 'open' }, { label: 'In progress', value: 'in_progress' }, { label: 'Waiting parts', value: 'waiting_parts' }, { label: 'Closed', value: 'closed' }]} />
                    <Select options={[{ label: 'Any line behavior', value: '' }, { label: 'Has stock lines', value: 'stock' }, { label: 'Has customer supplied', value: 'customer_supplied' }, { label: 'Has external service', value: 'external' }]} />
                    <Button variant="secondary">Filter</Button>
                </div>
            </Card>
            <JobCardTable rows={rows} />
        </div>
    );
}
