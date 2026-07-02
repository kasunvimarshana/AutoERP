import { useEffect, useState } from 'react';
import { LoadingState } from '@/shared/components/LoadingState';

export const GUARD_LOADING_DELAY_MS = 200;

export function GuardLoadingState({ label = 'Checking access...' }: { label?: string }) {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const timeoutId = window.setTimeout(() => setVisible(true), GUARD_LOADING_DELAY_MS);
        return () => window.clearTimeout(timeoutId);
    }, []);

    return visible ? <LoadingState label={label} fullPage /> : null;
}
