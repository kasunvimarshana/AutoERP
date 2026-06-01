import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceJobCard } from '../types/vehicleService.types';

export function ServiceInvoiceCreatePage() {
    const navigate = useNavigate();
    const [documentTypeId, setDocumentTypeId] = useState('');
    const [error, setError] = useState<string>();
    const [jobCardId, setJobCardId] = useState('');
    const [jobCards, setJobCards] = useState<VehicleServiceJobCard[]>([]);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let active = true;
        vehicleServiceApi.jobCards.list({ per_page: 25, status: 'completed' })
            .then((response) => active && setJobCards(response.data))
            .catch(() => vehicleServiceApi.jobCards.list({ per_page: 25 }).then((response) => active && setJobCards(response.data)));

        return () => {
            active = false;
        };
    }, []);

    async function generate(): Promise<void> {
        setSaving(true);
        setError(undefined);

        try {
            await vehicleServiceApi.invoices.generate(jobCardId, documentTypeId);
            navigate(`/vehicle-service/invoices/${jobCardId}`);
        } catch (caught) {
            setError(caught instanceof ApiError || caught instanceof Error ? caught.message : 'Unable to generate invoice.');
        } finally {
            setSaving(false);
        }
    }

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to="/vehicle-service/invoices"><Button variant="secondary">Cancel</Button></Link>}
                subtitle="Generate a service invoice document from a backend job card. Backend owns document number, totals, tax, AR and posting eligibility."
                title="Generate Service Invoice"
            />
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{error}</div> : null}
            <FormSection description="Select a completed/invoiceable job card. Document type ID is required by the current backend workflow endpoint." title="Invoice source">
                <div className="grid gap-4 md:grid-cols-2">
                    <label className="space-y-2 text-sm">
                        <span className="font-semibold text-slate-700">Job card</span>
                        <Select
                            onChange={(event) => setJobCardId(event.target.value)}
                            options={jobCards.map((jobCard) => ({ label: `${jobCard.jobCardNumber} - ${jobCard.partyContext.billingCustomer.name}`, value: jobCard.id }))}
                            value={jobCardId}
                        />
                    </label>
                    <label className="space-y-2 text-sm">
                        <span className="font-semibold text-slate-700">Document type ID</span>
                        <Input onChange={(event) => setDocumentTypeId(event.target.value)} placeholder="Vehicle Service Invoice document type id" value={documentTypeId} />
                    </label>
                </div>
                <div className="mt-4 flex justify-end">
                    <Button disabled={saving || !jobCardId || !documentTypeId} onClick={generate} variant="blue">
                        {saving ? 'Generating...' : 'Generate Invoice'}
                    </Button>
                </div>
            </FormSection>
        </div>
    );
}
