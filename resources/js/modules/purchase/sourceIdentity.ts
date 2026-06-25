import { parsePositiveInteger } from '@/shared/utils/routeParams';

export function normalizeSourceId(id: number | string | null | undefined): number | null {
    return parsePositiveInteger(id);
}

export function sourceKey(sourceType: string, sourceId: number | string | null | undefined): string | null {
    const normalizedId = normalizeSourceId(sourceId);
    return normalizedId === null ? null : `${sourceType}:${normalizedId}`;
}

export function sourceLineKey(
    sourceType: string,
    sourceId: number | string | null | undefined,
    sourceLineId: number | string | null | undefined,
): string | null {
    const normalizedSourceId = normalizeSourceId(sourceId);
    const normalizedLineId = normalizeSourceId(sourceLineId);

    return normalizedSourceId === null || normalizedLineId === null
        ? null
        : `${sourceType}:${normalizedSourceId}:${normalizedLineId}`;
}

export function sourceIdFromKey(key: string, sourceType: string): number | null {
    const [type, rawId] = key.split(':');
    if (type !== sourceType) return null;

    return normalizeSourceId(rawId);
}
