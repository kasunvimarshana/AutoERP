import type { ReactNode } from 'react';
import { ActionBar } from '../../../components/forms/ActionBar';

type WorkflowActionBarProps = {
    description?: string;
    children: ReactNode;
};

export function WorkflowActionBar({ description, children }: WorkflowActionBarProps) {
    return <ActionBar leading={description ? <p className="text-sm text-stone-500">{description}</p> : undefined}>{children}</ActionBar>;
}
