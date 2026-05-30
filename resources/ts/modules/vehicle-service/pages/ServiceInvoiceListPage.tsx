import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { ServiceInvoiceTable, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceInvoice } from '../types/vehicleService.types';

export function ServiceInvoiceListPage() {
    const [rows, setRows] = useState<VehicleServiceInvoice[]>([]);

    useEffect(() => {
        vehicleServiceApi.invoices.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to="/vehicle-service/job-cards"><Button>Invoiceable Jobs</Button></Link>}
                subtitle="Service invoices are generated from job cards. Backend owns totals, tax, discounts, documents, AR, and posting."
                title="Service Invoices"
            />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px]">
                    <Input placeholder="Search invoice, job card, customer..." />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Draft', value: 'draft' }, { label: 'Posted', value: 'posted' }, { label: 'Reversed', value: 'reversed' }]} />
                    <Button variant="secondary">Filter</Button>
                </div>
            </Card>
            <ServiceInvoiceTable rows={rows} />
        </div>
    );
}

export { ServiceInvoiceListPage as ServiceInvoicesPage };
