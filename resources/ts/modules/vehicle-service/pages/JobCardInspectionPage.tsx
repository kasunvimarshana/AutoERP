import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { InspectionPanel, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceInspection } from '../types/vehicleService.types';

export function JobCardInspectionPage() {
    const { id = '' } = useParams();
    const [rows, setRows] = useState<VehicleServiceInspection[]>([]);
    const [form, setForm] = useState({ notes: '', phase: '', result: '' });

    function reload(): void {
        vehicleServiceApi.inspections.list(id).then((response) => setRows(response.data));
    }

    useEffect(() => {
        reload();
    }, [id]);

    async function save(): Promise<void> {
        await vehicleServiceApi.inspections.upsert({
            inspection_number: `INSP-${Date.now()}`,
            job_card_id: Number(id),
            notes: form.notes,
            phase: form.phase,
            result: form.result,
            status: 'draft',
        });
        setForm({ notes: '', phase: '', result: '' });
        reload();
    }

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to={`/vehicle-service/job-cards/${id}`}><Button variant="secondary">Back to Job</Button></Link>}
                subtitle="Inspections are workshop checklists and outcomes. Backend owns status and audit history."
                title="Job Card Inspections"
            />
            <FormSection description="Record inspection phase, result, and notes." title="Inspection entry">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input onChange={(event) => setForm((current) => ({ ...current, phase: event.target.value }))} placeholder="Inspection phase" value={form.phase} />
                    <Input onChange={(event) => setForm((current) => ({ ...current, result: event.target.value }))} placeholder="Result" value={form.result} />
                    <Textarea onChange={(event) => setForm((current) => ({ ...current, notes: event.target.value }))} placeholder="Inspection notes" value={form.notes} />
                    <Button onClick={save} variant="blue">Save Inspection</Button>
                </div>
            </FormSection>
            <InspectionPanel rows={rows} />
        </div>
    );
}
