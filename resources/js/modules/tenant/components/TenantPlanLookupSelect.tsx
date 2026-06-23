import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { listTenantPlans } from '../tenantApi';
import type { TenantPlan } from '../tenantTypes';

interface TenantPlanLookupSelectProps {
    value: TenantPlan | null;
    onChange: (plan: TenantPlan | null) => void;
    disabled?: boolean;
    error?: string;
}

export function TenantPlanLookupSelect({ value, onChange, disabled = false, error }: TenantPlanLookupSelectProps) {
    const search = useCallback(async ({ search: term, page, perPage, signal }: {
        search: string;
        page: number;
        perPage: number;
        signal: AbortSignal;
    }) => {
        const result = await listTenantPlans({
            page,
            per_page: perPage,
            search: term || undefined,
            is_active: true,
        }, signal);

        return { data: result.data, meta: result.meta };
    }, []);

    return (
        <GenericLookupSelect
            label="Subscription plan"
            value={value}
            onChange={onChange}
            search={search}
            formatLabel={(plan) => `${plan.name} · ${plan.billing_interval}`}
            placeholder="Search active plans"
            disabled={disabled}
            error={error}
            loadOnOpen
            minSearchLength={0}
        />
    );
}
