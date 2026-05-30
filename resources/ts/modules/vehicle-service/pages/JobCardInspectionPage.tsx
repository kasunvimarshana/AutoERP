import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { InspectionPanel, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { getJobCardById } from '../mock/vehicleServiceMock';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceInspection } from '../types/vehicleService.types';

export function JobCardInspectionPage() {
    const { id = 'job-001' } = useParams();
    const [rows, setRows] = useState<VehicleServiceInspection[]>(getJobCardById(id).inspections);

    useEffect(() => {
        vehicleServiceApi.inspections.list(id).then((response) => setRows(response.data as VehicleServiceInspection[]));
    }, [id]);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to={`/vehicle-service/job-cards/${id}`}><Button variant="secondary">Back to Job</Button></Link>}
                subtitle="Inspections are workshop checklists and outcomes. Backend owns status and audit history."
                title="Job Card Inspections"
            />
            <FormSection description="Record inspection phase, result, and notes." title="Inspection entry">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input placeholder="Inspection phase" />
                    <Input placeholder="Inspection type" />
                    <Input placeholder="Result" />
                    <Textarea placeholder="Inspection notes" />
                </div>
            </FormSection>
            <InspectionPanel rows={rows} />
        </div>
    );
}
