import { Link } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Card } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { JobCardStepper } from '../components/JobCardStepper';
import { JobCardForm } from '../components/JobCardForm';

export default function JobCardCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <>
                        <Button variant="secondary">Drafts (12)</Button>
                        <Button>Save Progress</Button>
                    </>
                }
                description="Initiate a new service record for a customer vehicle"
                title="New Job Card"
            />

            <Card className="overflow-hidden">
                <JobCardStepper activeStep={1} />
                <div className="p-6 lg:p-8">
                    <JobCardForm />
                </div>
            </Card>

            <div className="flex justify-end px-2">
                <Link to="/job-cards/crew-assignment">
                    <Button variant="secondary">Open Crew Assignment View</Button>
                </Link>
            </div>
        </div>
    );
}
