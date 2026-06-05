import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { JobCardForm, VehicleServicePageHeader } from '../components/VehicleServiceComponents';

export function JobCardCreatePage() {
    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to="/vehicle-service/job-cards"><Button variant="secondary">Cancel</Button></Link>}
                subtitle="Create a workshop job card from intake, line sections, labour assignment, and backend previews. Customer-supplied items are explicitly no-stock-impact."
                title="New Job Card"
            />
            <JobCardForm />
        </div>
    );
}
