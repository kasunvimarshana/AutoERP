import { cn } from '../../utils/cn';

export type StepperStep = {
    label: string;
    value: string;
};

type StepperProps = {
    current: string;
    steps: StepperStep[];
};

export function Stepper({ current, steps }: StepperProps) {
    const currentIndex = Math.max(
        0,
        steps.findIndex((step) => step.value === current),
    );

    return (
        <div className="flex items-center gap-8 border-b border-slate-200">
            {steps.map((step, index) => {
                const isActive = step.value === current;
                const isComplete = index < currentIndex;

                return (
                    <div
                        className={cn(
                            '-mb-px flex h-14 items-center gap-3 border-b-2 pr-8 text-sm font-semibold',
                            isActive ? 'border-black text-slate-950' : 'border-transparent text-slate-400',
                        )}
                        key={step.value}
                    >
                        <span
                            className={cn(
                                'flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold',
                                isActive || isComplete ? 'bg-black text-white' : 'bg-slate-200 text-slate-500',
                            )}
                        >
                            {index + 1}
                        </span>
                        {step.label}
                    </div>
                );
            })}
        </div>
    );
}
