import { useCallback, useEffect, useEffectEvent, useState, type DependencyList } from 'react';
import { ApiError, toApiError } from '@/shared/api/apiError';

interface ApiState<T> {
    data: T | null;
    error: ApiError | null;
    loading: boolean;
}

type ApiDataUpdate<T> = T | ((current: T | null) => T | null);

/**
 * Loads an API resource and reloads it whenever a serializable dependency changes.
 * Dependency values must be primitives, null, or JSON-serializable value objects.
 */
export function useApi<T>(
    request: (signal: AbortSignal) => Promise<T>,
    dependencies: DependencyList,
    enabled = true,
    clearOnLoad = true,
) {
    const executeRequest = useEffectEvent(request);
    const dependencyKey = serializeDependencies(dependencies);
    const [state, setState] = useState<ApiState<T>>({ data: null, error: null, loading: enabled });
    const [version, setVersion] = useState(0);

    const reload = useCallback(() => setVersion((current) => current + 1), []);

    useEffect(() => {
        const controller = new AbortController();

        if (!enabled) {
            queueMicrotask(() => {
                if (controller.signal.aborted) return;
                setState((current) => ({
                    data: clearOnLoad ? null : current.data,
                    error: null,
                    loading: false,
                }));
            });
            return () => controller.abort();
        }

        queueMicrotask(() => {
            if (controller.signal.aborted) return;
            setState((current) => ({
                data: clearOnLoad ? null : current.data,
                loading: true,
                error: null,
            }));
        });

        void (async () => {
            try {
                const data = await executeRequest(controller.signal);
                if (!controller.signal.aborted) {
                    setState({ data, error: null, loading: false });
                }
            } catch (error: unknown) {
                if (!controller.signal.aborted) {
                    setState((current) => ({ ...current, error: toApiError(error), loading: false }));
                }
            }
        })();

        return () => controller.abort();
    }, [clearOnLoad, dependencyKey, enabled, version]);

    const setData = useCallback((update: ApiDataUpdate<T>) => {
        setState((current) => ({
            data: typeof update === 'function'
                ? (update as (current: T | null) => T | null)(current.data)
                : update,
            error: null,
            loading: false,
        }));
    }, []);

    return { ...state, reload, setData };
}

function serializeDependencies(dependencies: DependencyList): string {
    return JSON.stringify(dependencies, (_key, value: unknown) => {
        if (typeof value === 'bigint') return value.toString();
        if (value instanceof Date) return value.toISOString();
        return value;
    });
}
