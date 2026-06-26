import { describe, expect, it } from 'vitest';
import type { TenantCurrentSubscription, TenantPlanRevision } from '../tenantTypes';
import {
    availableSubscriptionActions,
    subscriptionAssignmentPayload,
    validateSubscriptionAction,
} from './tenantSubscriptionRules';

const current: TenantCurrentSubscription = {
    id: 1,
    tenant_id: 7,
    revision_number: 2,
    operation: 'renew',
    tenant_plan_revision_id: 11,
    supersedes_subscription_id: null,
    contract_status: 'active',
    effective_status: 'active',
    starts_at: '2026-06-01T00:00:00Z',
    trial_ends_at: null,
    ends_at: '2026-07-01T00:00:00Z',
    change_reason: null,
    plan_name: 'Growth',
    plan_slug: 'growth',
    plan_features_schema_version: 1,
    plan_features: { enabled_modules: [] },
    plan_limits_schema_version: 1,
    plan_limits: {},
    price: '100.00',
    currency_code: 'USD',
    currency_symbol: '$',
    billing_interval: 'month',
    revision: null,
    created_at: '2026-06-01T00:00:00Z',
    current_state: 'assigned',
    current_state_reason: null,
    current_state_changed_at: null,
    row_version: 3,
    assigned_at: '2026-06-01T00:00:00Z',
    assigned_by: 9,
};

const revision: TenantPlanRevision = {
    id: 11,
    tenant_plan_id: 3,
    revision_number: 4,
    features_schema_version: 1,
    features: { enabled_modules: [] },
    limits_schema_version: 1,
    limits: {},
    price: '100.00',
    currency_id: 1,
    currency: { id: 1, code: 'USD', name: 'US Dollar', symbol: '$', is_active: true },
    billing_interval: 'month',
    effective_at: '2026-06-01T00:00:00Z',
    change_note: 'Current price',
    created_at: '2026-06-01T00:00:00Z',
};

const now = new Date('2026-06-26T10:00:00Z');

describe('tenant subscription form rules', () => {
    it('does not offer the generic extend command for trials or open-ended contracts', () => {
        expect(availableSubscriptionActions({ ...current, contract_status: 'trial', trial_ends_at: current.ends_at, ends_at: null }))
            .toEqual(['renew', 'correct', 'cancel']);
        expect(availableSubscriptionActions({ ...current, ends_at: null }))
            .toEqual(['renew', 'correct', 'cancel']);
        expect(availableSubscriptionActions(current))
            .toEqual(['renew', 'extend', 'correct', 'cancel']);
    });

    it('rejects future starts and keeps trial and active expiry fields mutually exclusive', () => {
        expect(validateSubscriptionAction(
            'assign',
            null,
            revision,
            'active',
            '2026-06-27T10:00',
            '',
            '',
            '',
            now,
        )).toBe('Future subscription scheduling is not supported.');

        expect(validateSubscriptionAction(
            'assign',
            null,
            revision,
            'trial',
            '2026-06-26T09:00',
            '2026-07-01T09:00',
            '2026-08-01T09:00',
            '',
            now,
        )).toBe('Trial contracts cannot also have a contract end date.');
    });

    it('builds a trial payload without a contract end date', () => {
        const payload = subscriptionAssignmentPayload(
            11,
            'trial',
            '2026-06-26T09:00',
            '2026-07-01T09:00',
            '2026-08-01T09:00',
            ' Initial trial ',
        );

        expect(payload.contract_status).toBe('trial');
        expect(payload.trial_ends_at).toBe('2026-07-01T09:00:00.000Z');
        expect(payload.ends_at).toBeNull();
        expect(payload.reason).toBe('Initial trial');
    });
});
