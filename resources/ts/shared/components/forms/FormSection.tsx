import type { ReactNode } from 'react';
import { Card } from '../ui/Card';

type FormSectionProps = {
    children: ReactNode;
    description?: string;
    title?: string;
};

export function FormSection({ children, description, title }: FormSectionProps) {
    return (
        <Card className="p-5">
            {title ? (
                <div className="mb-5">
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-700">{title}</h2>
                    {description ? <p className="mt-1 text-sm text-slate-500">{description}</p> : null}
                </div>
            ) : null}
            {children}
        </Card>
    );
}
