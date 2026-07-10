import { useCallback, useEffect, useRef, useState } from "react";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import type { NamedResource } from "@/shared/types/common";
import { getRentalMetadata } from "../vehicleRentalApi";

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
    const [error, setError] = useState<ApiError | null>(null);
    const touchedRef = useRef(initialTouched);
    const defaultCurrencyRef = useRef<NamedResource | null>(null);

    useEffect(() => {
        defaultCurrencyRef.current = defaultCurrency;
    }, [defaultCurrency]);

    useEffect(() => {
        const controller = new AbortController();

        void getRentalMetadata(controller.signal)
            .then((metadata) => {
                if (controller.signal.aborted) return;

                const nextDefault = metadata.default_currency ?? null;
                defaultCurrencyRef.current = nextDefault;
                setDefaultCurrency(nextDefault);
                setError(null);
                setCurrency((current) => {
                    if (touchedRef.current || current !== null) {
                        return current;
                    }

                    return nextDefault;
                });
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            });

        return () => controller.abort();
    }, []);

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
        error,
        selectCurrency,
        setAuthoritativeCurrency,
        applyCurrencyDefault,
        resetCurrencyToDefault,
    };
}
