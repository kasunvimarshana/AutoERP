import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Button } from '../../../shared/components/ui/Button';
import { JobCardForm, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceJobCard } from '../types/vehicleService.types';

export function JobCardEditPage() {
    const { id = '' } = useParams();
    const [jobCard, setJobCard] = useState<VehicleServiceJobCard>();
    const [error, setError] = useState<string>();

    useEffect(() => {
        if (!id) {
            return;
        }

        vehicleServiceApi.jobCards.get(id)
            .then((response) => setJobCard(response.data))
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load job card.'));
    }, [id]);

    if (error) {
        return <EmptyState description={error} title="Job card unavailable" />;
    }

    if (!jobCard) {
        return <EmptyState description="Loading job card from backend..." title="Loading job card" />;
    }

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to={`/vehicle-service/job-cards/${jobCard.id}`}><Button variant="secondary">View</Button></Link>}
                subtitle="Edit job-card inputs. Backend remains authoritative for workflow, stock, pricing, tax, invoice, payment, and labour incentive results."
                title={`Edit ${jobCard.jobCardNumber}`}
            />
            <JobCardForm jobCard={jobCard} mode="edit" />
        </div>
    );
}
