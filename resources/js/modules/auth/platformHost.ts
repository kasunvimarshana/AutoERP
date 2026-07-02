export function isCentralPlatformHost(
    currentHost = window.location.host,
): boolean {
    if (import.meta.env.DEV) return false;
    if (localTenantFallbackEnabled()) return false;

    const configured = String(import.meta.env.VITE_PLATFORM_PUBLIC_URL ?? '').trim();
    if (configured === '') return false;

    try {
        return new URL(configured).host.toLowerCase() === currentHost.toLowerCase();
    } catch {
        return false;
    }
}

export function workspaceLoginUrl(value: string): string | null {
    const normalized = value.trim();
    if (normalized === '') return null;

    try {
        const url = new URL(normalized.includes('://') ? normalized : `https://${normalized}`);
        if (!['http:', 'https:'].includes(url.protocol) || url.hostname.trim() === '') return null;
        if (!import.meta.env.DEV && url.protocol !== 'https:') return null;

        url.pathname = '/login';
        url.search = '';
        url.hash = '';

        return url.toString();
    } catch {
        return null;
    }
}

function localTenantFallbackEnabled(): boolean {
    return String(import.meta.env.VITE_TENANT_LOCAL_FALLBACK_ENABLED ?? '').trim().toLowerCase() === 'true';
}
