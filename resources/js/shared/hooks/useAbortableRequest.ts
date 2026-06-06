import { useCallback, useEffect, useRef } from 'react';

export function useAbortableRequest() {
    const controllerRef = useRef<AbortController | null>(null);

    const nextSignal = useCallback(() => {
        controllerRef.current?.abort();
        controllerRef.current = new AbortController();
        return controllerRef.current.signal;
    }, []);

    const abort = useCallback(() => controllerRef.current?.abort(), []);

    useEffect(() => abort, [abort]);

    return { nextSignal, abort };
}
