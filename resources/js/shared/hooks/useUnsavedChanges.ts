import { useCallback, useEffect, useRef } from 'react';
import { useBeforeUnload, useBlocker, type BlockerFunction } from 'react-router-dom';

const defaultMessage = 'You have unsaved changes. Leave this page and discard them?';

export function useUnsavedChanges(active: boolean, message = defaultMessage) {
    const bypassNextNavigation = useRef(false);

    useBeforeUnload(useCallback((event) => {
        if (!active) return;
        event.preventDefault();
        event.returnValue = '';
    }, [active]));

    const shouldBlock = useCallback<BlockerFunction>(({ currentLocation, nextLocation }) => {
        if (bypassNextNavigation.current) {
            bypassNextNavigation.current = false;
            return false;
        }

        return active && !isTabOnlyChange(currentLocation, nextLocation);
    }, [active]);

    const blocker = useBlocker(shouldBlock);

    useEffect(() => {
        if (blocker.state !== 'blocked') return;

        if (window.confirm(message)) {
            blocker.proceed();
        } else {
            blocker.reset();
        }
    }, [blocker, message]);

    return useCallback(() => {
        if (!active) return true;
        if (!window.confirm(message)) return false;
        bypassNextNavigation.current = true;
        return true;
    }, [active, message]);
}

function isTabOnlyChange(
    current: { pathname: string; search: string; hash: string },
    destination: { pathname: string; search: string; hash: string },
): boolean {
    if (current.pathname !== destination.pathname || current.hash !== destination.hash) return false;

    const currentParams = new URLSearchParams(current.search);
    const destinationParams = new URLSearchParams(destination.search);
    const currentTab = currentParams.get('tab');
    const destinationTab = destinationParams.get('tab');
    currentParams.delete('tab');
    destinationParams.delete('tab');
    currentParams.sort();
    destinationParams.sort();

    return currentParams.toString() === destinationParams.toString() && currentTab !== destinationTab;
}
