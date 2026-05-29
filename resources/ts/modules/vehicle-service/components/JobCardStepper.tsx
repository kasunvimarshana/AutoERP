import { Stepper } from '../../../shared/components/ui/Stepper';

type JobCardStepperProps = {
    step: 'new-job' | 'crew-members';
};

export function JobCardStepper({ step }: JobCardStepperProps) {
    return (
        <Stepper
            current={step}
            steps={[
                { label: 'New Job', value: 'new-job' },
                { label: 'Crew Members', value: 'crew-members' },
            ]}
        />
    );
}
