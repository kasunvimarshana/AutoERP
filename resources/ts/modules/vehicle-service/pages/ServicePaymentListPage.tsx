import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { ServicePaymentPanel, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServicePayment } from '../types/vehicleService.types';

export function ServicePaymentListPage() {
    const [rows, setRows] = useState<VehicleServicePayment[]>([]);

    useEffect(() => {
        vehicleServiceApi.payments.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to="/vehicle-service/payments/new"><Button>Record Service Payment</Button></Link>}
                subtitle="Service payments are linked to VehicleService invoices/jobs. Backend owns allocation, balances, AR, refunds, and finance posting."
                title="Service Payments"
            />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px_160px]">
                    <Input placeholder="Search payment, invoice, customer..." />
                    <Select options={[{ label: 'Any method', value: '' }, { label: 'Cash', value: 'cash' }, { label: 'Bank', value: 'bank' }, { label: 'Card', value: 'card' }]} />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Draft', value: 'draft' }, { label: 'Posted', value: 'posted' }, { label: 'Reversed', value: 'reversed' }]} />
                    <Button variant="secondary">Filter</Button>
                </div>
            </Card>
            <ServicePaymentPanel payments={rows} />
        </div>
    );
}

export { ServicePaymentListPage as ServicePaymentsPage };
