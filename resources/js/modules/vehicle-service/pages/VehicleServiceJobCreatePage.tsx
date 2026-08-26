import { ContentHeader } from '@/shared/components/ContentHeader';
import { VehicleServiceJobForm } from '../components/VehicleServiceJobForm';

export default function VehicleServiceJobCreatePage() {
    return (
        <>
            <ContentHeader title="New vehicle service job" description="Select the vehicle and supervisor, then record the service details needed for the job." />
            <VehicleServiceJobForm />
        </>
    );
}
