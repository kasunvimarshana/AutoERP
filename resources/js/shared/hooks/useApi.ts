import { useCallback, useEffect, useRef, useState, type DependencyList } from 'react';
import { ApiError, toApiError } from '@/shared/api/apiError';

interface ApiState<T> {
    data: T | null;
    error: ApiError | null;
    loading: boolean;
}

export function useApi<T>(
    request: (signal: AbortSignal) => Promise<T>,
    dependencies: DependencyList,
    enabled = true,
    clearOnLoad = false,
) {
    const requestRef = useRef(request);
    requestRef.current = request;
    const [state, setState] = useState<ApiState<T>>({ data: null, error: null, loading: enabled });
    const [version, setVersion] = useState(0);

    const reload = useCallback(() => setVersion((current) => current + 1), []);

    useEffect(() => {
        if (!enabled) {
            setState((current) => ({
                data: clearOnLoad ? null : current.data,
                error: null,
                loading: false,
            }));
            return;
        }

        const controller = new AbortController();
        setState((current) => ({
            data: clearOnLoad ? null : current.data,
            loading: true,
            error: null,
        }));
        void (async () => {
            try {
                const data = await requestRef.current(controller.signal);
                if (!controller.signal.aborted) {
                    setState((current) => ({ ...current, data, error: null }));
                }
            } catch (error: unknown) {
                if (!controller.signal.aborted) {
                    setState((current) => ({ ...current, error: toApiError(error) }));
                }
            } finally {
                if (!controller.signal.aborted) {
                    setState((current) => ({ ...current, loading: false }));
                }
            }
        })();

        return () => controller.abort();
        // Dependencies are deliberately supplied by each caller.
    }, [...dependencies, enabled, clearOnLoad, version]);

    return { ...state, reload, setData: (data: T) => setState({ data, error: null, loading: false }) };
}
