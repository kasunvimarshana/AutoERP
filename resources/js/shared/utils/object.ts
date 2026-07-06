import type { UnknownRecord } from '@/shared/types/common';

export function compactObject<T extends UnknownRecord>(value: T): Partial<T> {
    return Object.fromEntries(
        Object.entries(value).filter(([, entry]) => entry !== '' && entry !== null && entry !== undefined),
    ) as Partial<T>;
}

export function humanize(value?: string | null): string {
    if (!value) return '-';
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function readableRelation(value: unknown): string {
    if (!value || typeof value !== 'object') return '-';
    const resource = value as Record<string, unknown>;
    return String(
        resource.name
        ?? resource.display_name
        ?? resource.code
        ?? resource.agreement_number
        ?? resource.allocation_number
        ?? resource.vehicle_number
        ?? resource.registration_number
        ?? resource.invoice_number
        ?? resource.payment_number
        ?? resource.replacement_number
        ?? resource.usage_number
        ?? resource.purchase_order_number
        ?? resource.journal_number
        ?? '-',
    );
}
