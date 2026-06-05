import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Input } from '../../../shared/components/ui/Input';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { DiagnosticsPanel, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceDiagnostic } from '../types/vehicleService.types';

export function JobCardDiagnosticsPage() {
    const { id = '' } = useParams();
    const [rows, setRows] = useState<VehicleServiceDiagnostic[]>([]);
    const [form, setForm] = useState({ findings: '', phase: '', recommendation: '' });

    function reload(): void {
        vehicleServiceApi.diagnostics.list(id).then((response) => setRows(response.data));
    }

    useEffect(() => {
        reload();
    }, [id]);

    async function save(): Promise<void> {
        await vehicleServiceApi.diagnostics.upsert({
            diagnostic_number: `DIAG-${Date.now()}`,
            findings: form.findings,
            job_card_id: Number(id),
            phase: form.phase,
            recommendation: form.recommendation,
            status: 'draft',
        });
        setForm({ findings: '', phase: '', recommendation: '' });
        reload();
    }

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to={`/vehicle-service/job-cards/${id}`}><Button variant="secondary">Back to Job</Button></Link>}
                subtitle="Diagnostics capture workshop findings. Backend owns validation, status, and links to job-card workflow."
                title="Job Card Diagnostics"
            />
            <FormSection description="Create or update diagnostic findings against the job card." title="Diagnostic entry">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input onChange={(event) => setForm((current) => ({ ...current, phase: event.target.value }))} placeholder="Diagnostic phase" value={form.phase} />
                    <Textarea onChange={(event) => setForm((current) => ({ ...current, findings: event.target.value }))} placeholder="Findings" value={form.findings} />
                    <Textarea onChange={(event) => setForm((current) => ({ ...current, recommendation: event.target.value }))} placeholder="Recommendation" value={form.recommendation} />
                    <Button onClick={save} variant="blue">Save Diagnostic</Button>
                </div>
            </FormSection>
            <DiagnosticsPanel rows={rows} />
        </div>
    );
}
