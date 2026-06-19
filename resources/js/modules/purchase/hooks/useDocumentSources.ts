import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { sourceIdFromKey, sourceKey } from '../sourceIdentity';

export interface DocumentSource<TType extends string = string> {
    type: TType;
    id: number;
    label: string;
}

export interface DocumentSourceLoadResult<TType extends string, TLine, TContext = undefined> {
    source?: DocumentSource<TType>;
    lines: TLine[];
    context?: TContext;
}

export interface AddDocumentSourceOptions<TType extends string, TLine, TContext = undefined> {
    type: TType;
    id: number;
    fallbackLabel: string;
    load: (signal: AbortSignal) => Promise<DocumentSourceLoadResult<TType, TLine, TContext>>;
    onSuccess?: (result: DocumentSourceLoadResult<TType, TLine, TContext>) => void;
}

export function useDocumentSources<TType extends string, TLine>({
    getLineKey,
    getLineSourceKey,
}: {
    getLineKey: (line: TLine) => string | null;
    getLineSourceKey: (line: TLine) => string | null;
}) {
    const [sources, setSourcesState] = useState<Array<DocumentSource<TType>>>([]);
    const [lines, setLinesState] = useState<TLine[]>([]);
    const [loadingKeys, setLoadingKeysState] = useState<Set<string>>(() => new Set());
    const selectedKeysRef = useRef(new Set<string>());
    const loadingKeysRef = useRef(new Set<string>());
    const requestRef = useRef(new Map<string, { controller: AbortController; generation: number }>());
    const generationRef = useRef(0);
    const mountedRef = useRef(true);
    const unmountAbortTimerRef = useRef<number | null>(null);

    const setLoading = useCallback((key: string, loading: boolean) => {
        if (loading) {
            loadingKeysRef.current.add(key);
        } else {
            loadingKeysRef.current.delete(key);
        }

        if (mountedRef.current) setLoadingKeysState(new Set(loadingKeysRef.current));
    }, []);

    const abortSource = useCallback((key: string) => {
        const request = requestRef.current.get(key);
        request?.controller.abort();
        requestRef.current.delete(key);
        setLoading(key, false);
    }, [setLoading]);

    const abortAll = useCallback(() => {
        generationRef.current += 1;
        requestRef.current.forEach((request) => request.controller.abort());
        requestRef.current.clear();
        loadingKeysRef.current.clear();
        if (mountedRef.current) setLoadingKeysState(new Set());
    }, []);

    const setLines = useCallback((nextLines: TLine[]) => {
        setLinesState(dedupeLines(nextLines, getLineKey));
    }, [getLineKey]);

    const clearSources = useCallback(() => {
        abortAll();
        selectedKeysRef.current.clear();
        setSourcesState([]);
        setLinesState([]);
    }, [abortAll]);

    const isSourceSelected = useCallback((key: string) => selectedKeysRef.current.has(key), []);
    const isSourceLoading = useCallback((key: string) => loadingKeysRef.current.has(key), []);
    const isSourceUnavailable = useCallback((key: string) => (
        selectedKeysRef.current.has(key) || loadingKeysRef.current.has(key)
    ), []);

    const addSource = useCallback(async <TContext = undefined>({
        type,
        id,
        fallbackLabel,
        load,
        onSuccess,
    }: AddDocumentSourceOptions<TType, TLine, TContext>): Promise<boolean> => {
        const key = sourceKey(type, id);
        if (!key || selectedKeysRef.current.has(key) || loadingKeysRef.current.has(key)) return false;

        const controller = new AbortController();
        const generation = generationRef.current;
        requestRef.current.set(key, { controller, generation });
        setLoading(key, true);

        try {
            let result: DocumentSourceLoadResult<TType, TLine, TContext>;
            try {
                result = await load(controller.signal);
            } catch (requestError) {
                const currentRequest = requestRef.current.get(key);
                if (
                    controller.signal.aborted
                    || !mountedRef.current
                    || currentRequest?.controller !== controller
                    || currentRequest.generation !== generation
                    || generationRef.current !== generation
                ) {
                    return false;
                }

                throw requestError;
            }

            const currentRequest = requestRef.current.get(key);
            if (
                controller.signal.aborted
                || !mountedRef.current
                || currentRequest?.controller !== controller
                || currentRequest.generation !== generation
                || generationRef.current !== generation
            ) {
                return false;
            }

            const resolvedSource = result.source ?? { type, id, label: fallbackLabel };
            const resolvedKey = sourceKey(resolvedSource.type, resolvedSource.id);
            if (resolvedKey !== key || selectedKeysRef.current.has(key)) return false;

            onSuccess?.(result);

            selectedKeysRef.current.add(key);
            setSourcesState((current) => dedupeSources([...current, resolvedSource]));
            setLinesState((current) => dedupeLines([...current, ...result.lines], getLineKey));

            return true;
        } finally {
            const currentRequest = requestRef.current.get(key);
            if (currentRequest?.controller === controller) {
                requestRef.current.delete(key);
                if (mountedRef.current) {
                    setLoading(key, false);
                } else {
                    loadingKeysRef.current.delete(key);
                }
            }
        }
    }, [getLineKey, setLoading]);

    const removeSource = useCallback((source: Pick<DocumentSource<TType>, 'type' | 'id'>) => {
        const key = sourceKey(source.type, source.id);
        if (!key) return;

        abortSource(key);
        selectedKeysRef.current.delete(key);
        setSourcesState((current) => current.filter((item) => sourceKey(item.type, item.id) !== key));
        setLinesState((current) => current.filter((line) => getLineSourceKey(line) !== key));
    }, [abortSource, getLineSourceKey]);

    const excludeIdsForType = useCallback((type: TType): number[] => {
        const selectedIds = sources
            .filter((source) => source.type === type)
            .map((source) => source.id);
        const loadingIds = Array.from(loadingKeys)
            .map((key) => sourceIdFromKey(key, type))
            .filter(isNumber);

        return [...new Set([...selectedIds, ...loadingIds])];
    }, [loadingKeys, sources]);

    const hasLoadingSources = useMemo(() => loadingKeys.size > 0, [loadingKeys]);

    useEffect(() => {
        mountedRef.current = true;
        if (unmountAbortTimerRef.current !== null) {
            window.clearTimeout(unmountAbortTimerRef.current);
            unmountAbortTimerRef.current = null;
        }

        return () => {
            mountedRef.current = false;
            unmountAbortTimerRef.current = window.setTimeout(() => abortAll(), 0);
        };
    }, [abortAll]);

    return {
        sources,
        lines,
        loadingKeys,
        hasLoadingSources,
        setLines,
        addSource,
        removeSource,
        clearSources,
        abortAll,
        isSourceSelected,
        isSourceLoading,
        isSourceUnavailable,
        excludeIdsForType,
    };
}

function dedupeSources<TType extends string>(sources: Array<DocumentSource<TType>>): Array<DocumentSource<TType>> {
    const seen = new Set<string>();

    return sources.filter((source) => {
        const key = sourceKey(source.type, source.id);
        if (!key || seen.has(key)) return false;
        seen.add(key);
        return true;
    });
}

function dedupeLines<TLine>(lines: TLine[], getLineKey: (line: TLine) => string | null): TLine[] {
    const seen = new Set<string>();

    return lines.filter((line) => {
        const key = getLineKey(line);
        if (!key || seen.has(key)) return false;
        seen.add(key);
        return true;
    });
}

function isNumber(value: number | null): value is number {
    return value !== null;
}
