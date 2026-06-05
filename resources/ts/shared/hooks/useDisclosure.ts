import { useCallback, useState } from 'react';

export function useDisclosure(defaultOpen = false) {
    const [open, setOpen] = useState(defaultOpen);

    return {
        close: useCallback(() => setOpen(false), []),
        open,
        openPanel: useCallback(() => setOpen(true), []),
        setOpen,
        toggle: useCallback(() => setOpen((current) => !current), []),
    };
}
