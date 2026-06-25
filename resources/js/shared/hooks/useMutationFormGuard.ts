import { useCallback, useState } from 'react';
import { useUnsavedChanges } from './useUnsavedChanges';

export function useMutationFormGuard(saving: boolean) {
    const [dirty, setDirty] = useState(false);
    useUnsavedChanges(dirty && !saving);

    return {
        dirty,
        markDirty: useCallback(() => setDirty(true), []),
        markSaved: useCallback(() => setDirty(false), []),
        resetDirty: useCallback(() => setDirty(false), []),
    };
}
