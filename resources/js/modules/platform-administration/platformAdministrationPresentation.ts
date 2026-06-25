export function formatPlatformDateTime(value: string | null | undefined): string {
    if (!value) return 'Not available';
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'medium' }).format(date);
}

export function humanizePlatformValue(value: string | null | undefined): string {
    if (!value) return 'Not available';
    return value
        .replace(/^platform\./, '')
        .replaceAll('.', ' · ')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function formatBytes(bytes: number): string {
    if (!Number.isFinite(bytes) || bytes <= 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const amount = bytes / 1024 ** exponent;
    return `${amount.toFixed(amount >= 10 || exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}

export function permissionGroup(permission: string): string {
    const segments = permission.split('.');
    return segments[1] ? humanizePlatformValue(segments[1]) : 'Platform';
}


export interface PlatformAuditLinkFilters {
    tenant_id?: number;
    event_name?: string;
    source_module?: string;
    subject_type?: string;
    subject_id?: string | number;
}

export function platformAuditHref(filters: PlatformAuditLinkFilters): string {
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(filters)) {
        if (value !== undefined && value !== null && String(value).trim() !== '') {
            params.set(key, String(value));
        }
    }
    const query = params.toString();
    return query === '' ? '/administration/platform-audit' : `/administration/platform-audit?${query}`;
}
