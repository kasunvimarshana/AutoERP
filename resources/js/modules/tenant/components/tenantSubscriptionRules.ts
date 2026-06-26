import type {
    TenantCurrentSubscription,
    TenantPlanRevision,
    TenantSubscriptionContractStatus,
} from '../tenantTypes';

export type SubscriptionAction = 'assign' | 'renew' | 'extend' | 'correct' | 'cancel';

export function requiresPlan(action: SubscriptionAction): boolean {
    return action === 'assign' || action === 'renew' || action === 'correct';
}

export function requiresPeriod(action: SubscriptionAction): boolean {
    return requiresPlan(action);
}

export function defaultSubscriptionAction(current: TenantCurrentSubscription | null): SubscriptionAction {
    return !current || current.current_state === 'cancelled' || current.current_state === 'expired'
        ? 'assign'
        : 'renew';
}

export function availableSubscriptionActions(current: TenantCurrentSubscription | null): SubscriptionAction[] {
    if (!current || current.current_state === 'cancelled' || current.current_state === 'expired') {
        return ['assign'];
    }

    const actions: SubscriptionAction[] = ['renew'];
    if (current.contract_status === 'active' && current.ends_at) actions.push('extend');
    actions.push('correct', 'cancel');

    return actions;
}

export function subscriptionActionLabel(action: SubscriptionAction): string {
    return ({
        assign: 'Assign subscription',
        renew: 'Renew subscription',
        extend: 'Extend end date',
        correct: 'Correct subscription',
        cancel: 'Cancel subscription',
    })[action];
}

export function normalizedReason(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

export function toIsoOrNull(value: string): string | null {
    return value === '' ? null : new Date(value).toISOString();
}

export function toIso(value: string): string {
    return new Date(value).toISOString();
}

export function toLocalDateTime(value: string | null | undefined): string {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const offset = date.getTimezoneOffset() * 60_000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

export function subscriptionAssignmentPayload(
    revisionId: number,
    contractStatus: TenantSubscriptionContractStatus,
    startsAt: string,
    trialEndsAt: string,
    endsAt: string,
    reason: string,
) {
    return {
        tenant_plan_revision_id: revisionId,
        contract_status: contractStatus,
        starts_at: toIsoOrNull(startsAt),
        trial_ends_at: contractStatus === 'trial' ? toIsoOrNull(trialEndsAt) : null,
        ends_at: contractStatus === 'active' ? toIsoOrNull(endsAt) : null,
        reason: normalizedReason(reason),
    };
}

export function validateSubscriptionAction(
    action: SubscriptionAction,
    current: TenantCurrentSubscription | null,
    revision: TenantPlanRevision | null,
    status: TenantSubscriptionContractStatus,
    startsAt: string,
    trialEndsAt: string,
    endsAt: string,
    reason: string,
    now: Date = new Date(),
): string | null {
    if (action === 'cancel') {
        return current && reason.trim().length > 0 ? null : 'A cancellation reason is required.';
    }

    if (action === 'extend') {
        if (!current) return 'A current subscription is required.';
        if (current.contract_status !== 'active' || !current.ends_at) {
            return 'Only fixed-term active contracts can be extended.';
        }
        if (endsAt === '') return 'Select the new contract end date.';

        const next = new Date(endsAt);
        const previous = new Date(current.ends_at);
        if (Number.isNaN(next.getTime())) return 'Select a valid contract end date.';
        if (next <= previous) return 'The new end date must be later than the current end date.';

        return null;
    }

    if (!revision) return 'Select a plan and an exact revision.';
    if (action === 'correct' && startsAt === '') return 'A correction requires an explicit start date.';
    if (action === 'correct' && reason.trim().length === 0) return 'A correction reason is required.';

    const start = startsAt === '' ? now : new Date(startsAt);
    if (Number.isNaN(start.getTime())) return 'Select a valid start date.';
    if (start.getTime() > now.getTime() + 60_000) return 'Future subscription scheduling is not supported.';

    if (status === 'trial') {
        if (endsAt !== '') return 'Trial contracts cannot also have a contract end date.';
        if (trialEndsAt === '') return 'A trial end date is required.';

        const trialEnd = new Date(trialEndsAt);
        if (Number.isNaN(trialEnd.getTime()) || trialEnd <= start) {
            return 'Trial end must be later than the start date.';
        }

        return null;
    }

    if (trialEndsAt !== '') return 'Active contracts cannot have a trial end date.';
    if (endsAt !== '') {
        const end = new Date(endsAt);
        if (Number.isNaN(end.getTime()) || end <= start) {
            return 'Contract end must be later than the start date.';
        }
    }

    return null;
}
