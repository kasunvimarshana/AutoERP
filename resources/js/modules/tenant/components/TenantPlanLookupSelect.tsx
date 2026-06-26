import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { listSubscriptionPlans } from '../tenantApi';
import type { TenantPlan } from '../tenantTypes';

interface TenantPlanLookupSelectProps {
    value: TenantPlan | null;
    onChange: (plan: TenantPlan | null) => void;
    disabled?: boolean;
    error?: string;
    label?: string;
}

export function TenantPlanLookupSelect({ value, onChange, disabled = false, error, label = 'Subscription plan' }: TenantPlanLookupSelectProps) {
    const search = useCallback(async ({ search: term, page, perPage, signal }: {
        search: string;
        page: number;
        perPage: number;
        signal: AbortSignal;
    }) => {
        const result = await listSubscriptionPlans({
            page,
            per_page: perPage,
            search: term || undefined,
        }, signal);

        return { data: result.data, meta: result.meta };
    }, []);

    return (
        <GenericLookupSelect
            label={label}
            value={value}
            onChange={onChange}
            search={search}
            formatLabel={(plan) => plan.latest_revision ? `${plan.name} · revision ${plan.latest_revision.revision_number} · ${plan.latest_revision.billing_interval}` : `${plan.name} · no revision`}
            placeholder="Search active plans"
            disabled={disabled}
            error={error}
            loadOnOpen
            minSearchLength={0}
        />
    );
}
