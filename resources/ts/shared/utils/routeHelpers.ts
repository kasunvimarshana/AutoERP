export function joinRoute(...parts: string[]): string {
    return `/${parts.map((part) => part.replace(/^\/+|\/+$/g, '')).filter(Boolean).join('/')}`;
}
