import { cn } from '../../utils/cn';

type WorkflowStep = {
    description?: string;
    label: string;
    status?: 'done' | 'current' | 'pending';
};

const defaultSteps: WorkflowStep[] = [
    { label: 'Draft', status: 'done' },
    { label: 'Submitted', status: 'current' },
    { label: 'Approved', status: 'pending' },
    { label: 'Posted', status: 'pending' },
];

export function WorkflowTimeline({ steps = defaultSteps }: { steps?: WorkflowStep[] }) {
    return (
        <div className="space-y-3">
            {steps.map((step, index) => (
                <div className="flex items-start gap-3" key={step.label}>
                    <span
                        className={cn(
                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            step.status === 'done' && 'bg-slate-950 text-white',
                            step.status === 'current' && 'bg-blue-600 text-white',
                            (!step.status || step.status === 'pending') && 'bg-slate-100 text-slate-500',
                        )}
                    >
                        {index + 1}
                    </span>
                    <div>
                        <p className="text-sm font-semibold text-slate-800">{step.label}</p>
                        {step.description ? <p className="mt-0.5 text-xs text-slate-500">{step.description}</p> : null}
                    </div>
                </div>
            ))}
        </div>
    );
}
