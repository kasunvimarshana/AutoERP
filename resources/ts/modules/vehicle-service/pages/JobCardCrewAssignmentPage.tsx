import { PageHeader } from '../../../components/layout/PageHeader';
import { Card } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { JobCardStepper } from '../components/JobCardStepper';
import { CrewAssignmentPanel } from '../components/CrewAssignmentPanel';

export default function JobCardCrewAssignmentPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <>
                        <Button variant="secondary">Drafts (12)</Button>
                        <Button>Save Progress</Button>
                    </>
                }
                description="Crew allocation and sub-item assignment for the selected job card"
                title="New Job Card"
            />

            <Card className="overflow-hidden">
                <JobCardStepper activeStep={2} />
                <div className="p-6 lg:p-8">
                    <CrewAssignmentPanel />
                </div>
            </Card>
        </div>
    );
}
