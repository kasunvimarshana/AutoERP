export function parsePositiveInteger(value: string | null | undefined, fallback: number) {
    const parsed = Number(value);

    if (!Number.isFinite(parsed) || parsed <= 0) {
        return fallback;
    }

    return Math.trunc(parsed);
}

export function parseBooleanSearchParam(value: string | null | undefined) {
    if (value === '1' || value === 'true') {
        return true;
    }

    if (value === '0' || value === 'false') {
        return false;
    }

    return undefined;
}

export function formatDate(value: string | null | undefined) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}

export function formatDateTime(value: string | null | undefined) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

export function formatCurrency(value: number | string | null | undefined) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    const amount = typeof value === 'number' ? value : Number(value);
    if (!Number.isFinite(amount)) {
        return String(value);
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

export function formatQuantity(value: number | string | null | undefined, maximumFractionDigits = 3) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    const amount = typeof value === 'number' ? value : Number(value);
    if (!Number.isFinite(amount)) {
        return String(value);
    }

    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits,
    }).format(amount);
}

export function getStatusTone(status: string | boolean | null | undefined): 'default' | 'success' | 'warning' | 'danger' {
    if (typeof status === 'boolean') {
        return status ? 'success' : 'default';
    }

    switch (status) {
        case 'active':
        case 'approved':
        case 'confirmed':
        case 'complete':
        case 'completed':
        case 'received':
        case 'paid':
        case 'posted':
        case 'delivered':
            return 'success';
        case 'inactive':
        case 'draft':
        case 'sent':
        case 'picking':
        case 'packed':
        case 'partial':
        case 'partial_paid':
        case 'in_progress':
        case 'in_transit':
        case 'shipped':
        case 'overdue':
        case 'disputed':
        case 'quarantine':
        case 'transit':
        case 'virtual':
        case 'warning':
            return 'default';
        case 'closed':
        case 'blocked':
        case 'suspended':
        case 'damaged':
        case 'expired':
        case 'defective':
        case 'scrapped':
        case 'cancelled':
            return 'danger';
        case 'standard':
        case 'restock':
        case 'return_to_vendor':
        case 'restocked':
        case 'individual':
        case 'company':
            return 'default';
        case 'pending':
        case 'partial_received':
        case 'shipment':
        case 'receipt':
        case 'adjustment':
        case 'adjustment_in':
        case 'adjustment_out':
        case 'reservation':
        case 'reservation_release':
        case 'write_off':
        case 'cycle_count':
        case 'return_in':
        case 'return_out':
            return 'warning';
        default:
            return 'default';
    }
}
