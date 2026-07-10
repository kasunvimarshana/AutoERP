import { useCallback, useEffect, useRef, useState } from "react";
import type { NamedResource } from "@/shared/types/common";
import { useRentalMetadata } from "./useRentalMetadata";

interface RentalCurrencyDefaultOptions {
    initialCurrency?: NamedResource | null;
    initialTouched?: boolean;
}

export function useRentalCurrencyDefault({
    initialCurrency = null,
    initialTouched = false,
}: RentalCurrencyDefaultOptions = {}) {
    const [currency, setCurrency] = useState<NamedResource | null>(initialCurrency);
    const [defaultCurrency, setDefaultCurrency] = useState<NamedResource | null>(null);
    const metadata = useRentalMetadata();
    const touchedRef = useRef(initialTouched);
    const defaultCurrencyRef = useRef<NamedResource | null>(null);

    useEffect(() => {
        defaultCurrencyRef.current = defaultCurrency;
    }, [defaultCurrency]);

    useEffect(() => {
        if (!metadata.data) return;

        const nextDefault = metadata.data.default_currency ?? null;
        let cancelled = false;
        queueMicrotask(() => {
            if (cancelled) return;

            defaultCurrencyRef.current = nextDefault;
            setDefaultCurrency(nextDefault);
            setCurrency((current) => {
                if (touchedRef.current || current !== null) {
                    return current;
                }

                return nextDefault;
            });
        });

        return () => {
            cancelled = true;
        };
    }, [metadata.data]);

    const selectCurrency = useCallback((next: NamedResource | null) => {
        touchedRef.current = true;
        setCurrency(next);
    }, []);

    const setAuthoritativeCurrency = useCallback((next: NamedResource | null) => {
        touchedRef.current = true;
        setCurrency(next);
    }, []);

    const applyCurrencyDefault = useCallback((next: NamedResource | null | undefined) => {
        if (touchedRef.current) return;

        setCurrency(next ?? defaultCurrencyRef.current);
    }, []);

    const resetCurrencyToDefault = useCallback(() => {
        touchedRef.current = false;
        setCurrency(defaultCurrencyRef.current);
    }, []);

    return {
        currency,
        defaultCurrency,
        metadata: metadata.data,
        metadataLoading: metadata.loading,
        error: metadata.error,
        selectCurrency,
        setAuthoritativeCurrency,
        applyCurrencyDefault,
        resetCurrencyToDefault,
    };
}
