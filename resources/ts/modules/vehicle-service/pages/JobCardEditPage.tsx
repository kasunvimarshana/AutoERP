import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { JobCardForm, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { getJobCardById } from '../mock/vehicleServiceMock';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceJobCard } from '../types/vehicleService.types';

export function JobCardEditPage() {
    const { id = 'job-001' } = useParams();
    const [jobCard, setJobCard] = useState<VehicleServiceJobCard>(getJobCardById(id));

    useEffect(() => {
        vehicleServiceApi.jobCards.get(id).then((response) => setJobCard(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<><Link to={`/vehicle-service/job-cards/${jobCard.id}`}><Button variant="secondary">View</Button></Link><Button>Save Draft</Button><Button variant="blue">Submit Backend Preview</Button></>}
                subtitle="Edit job-card inputs. Backend remains authoritative for workflow, stock, pricing, tax, invoice, payment, and labour incentive results."
                title={`Edit ${jobCard.jobCardNumber}`}
            />
            <JobCardForm jobCard={jobCard} />
        </div>
    );
}
