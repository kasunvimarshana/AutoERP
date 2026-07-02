const PUBLIC_API_EXACT_PATHS = new Set([
    '/api/v1/auth/login',
    '/api/v1/auth/refresh',
    '/api/v1/auth/oauth/token',
    '/api/v1/platform/auth/login',
    '/api/v1/platform/auth/refresh',
]);

export function isPublicApiRequest(url: string | undefined): boolean {
    const path = requestPath(url);
    if (path === '') return false;

    return PUBLIC_API_EXACT_PATHS.has(path);
}

export function requestPath(url: string | undefined): string {
    if (!url) return '';

    try {
        return new URL(url, 'http://autoerp.local').pathname;
    } catch {
        return url.split('?')[0] ?? '';
    }
}
