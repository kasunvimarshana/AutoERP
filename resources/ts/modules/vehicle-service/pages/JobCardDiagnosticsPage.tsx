import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Input } from '../../../shared/components/ui/Input';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { DiagnosticsPanel, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { getJobCardById } from '../mock/vehicleServiceMock';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceDiagnostic } from '../types/vehicleService.types';

export function JobCardDiagnosticsPage() {
    const { id = 'job-001' } = useParams();
    const [rows, setRows] = useState<VehicleServiceDiagnostic[]>(getJobCardById(id).diagnostics);

    useEffect(() => {
        vehicleServiceApi.diagnostics.list(id).then((response) => setRows(response.data as VehicleServiceDiagnostic[]));
    }, [id]);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to={`/vehicle-service/job-cards/${id}`}><Button variant="secondary">Back to Job</Button></Link>}
                subtitle="Diagnostics capture workshop findings. Backend owns validation, status, and links to job-card workflow."
                title="Job Card Diagnostics"
            />
            <FormSection description="Create or update diagnostic findings against the job card." title="Diagnostic entry">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input placeholder="Diagnostic phase" />
                    <Input placeholder="Diagnostic type" />
                    <Textarea placeholder="Findings" />
                    <Textarea placeholder="Recommendation" />
                </div>
            </FormSection>
            <DiagnosticsPanel rows={rows} />
        </div>
    );
}
