import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';

type JobCardStep = 'new-job' | 'crew-members';

type JobCardContextValue = {
    setStep: (step: JobCardStep) => void;
    step: JobCardStep;
};

const JobCardContext = createContext<JobCardContextValue | null>(null);

export function JobCardContextProvider({ children }: { children: ReactNode }) {
    const [step, setStep] = useState<JobCardStep>('new-job');
    const value = useMemo(() => ({ setStep, step }), [step]);

    return <JobCardContext.Provider value={value}>{children}</JobCardContext.Provider>;
}

export function useJobCardContext() {
    const context = useContext(JobCardContext);

    if (!context) {
        throw new Error('useJobCardContext must be used inside JobCardContextProvider.');
    }

    return context;
}
