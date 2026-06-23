export function resolveSameOriginUrl(value: string): string | null {
    const candidate = value.trim();
    if (candidate === '') return null;

    try {
        const url = new URL(candidate, window.location.origin);
        if (url.origin !== window.location.origin) return null;
        if (url.protocol !== 'http:' && url.protocol !== 'https:') return null;
        return url.href;
    } catch {
        return null;
    }
}

export function openSameOriginUrl(value: string): boolean {
    const url = resolveSameOriginUrl(value);
    if (!url) return false;
    window.open(url, '_blank', 'noopener,noreferrer');
    return true;
}
