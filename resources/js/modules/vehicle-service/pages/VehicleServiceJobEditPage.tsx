import { useParams } from 'react-router-dom';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { VehicleServiceJobForm } from '../components/VehicleServiceJobForm';
import { getVehicleServiceJob } from '../vehicleServiceApi';

export default function VehicleServiceJobEditPage() {
    const id = Number(useParams().id);
    const result = useApi((signal) => getVehicleServiceJob(id, signal), [id]);
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    if (!['draft', 'inspected', 'in_progress'].includes(result.data.status)) {
        return <CapabilityNotice>This service job can no longer be edited.</CapabilityNotice>;
    }

    return (
        <>
            <ContentHeader title={`Edit ${result.data.job_number}`} />
            <VehicleServiceJobForm job={result.data} />
        </>
    );
}
