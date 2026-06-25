import { useCallback, useEffect } from 'react';
import { useBeforeUnload, useLocation } from 'react-router-dom';

const defaultMessage = 'You have unsaved changes. Leave this page and discard them?';

export function useUnsavedChanges(active: boolean, message = defaultMessage) {
    const location = useLocation();

    useBeforeUnload(useCallback((event) => {
        if (!active) return;
        event.preventDefault();
        event.returnValue = '';
    }, [active]));

    useEffect(() => {
        if (!active) return;

        let currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
        const confirmHistoryNavigation = () => {
            const destination = new URL(window.location.href);
            const previous = new URL(currentUrl, window.location.origin);
            if (isTabOnlyChange(previous, destination)) {
                currentUrl = `${destination.pathname}${destination.search}${destination.hash}`;
                return;
            }
            if (window.confirm(message)) return;

            window.history.pushState(null, '', currentUrl);
        };

        window.addEventListener('popstate', confirmHistoryNavigation);
        return () => window.removeEventListener('popstate', confirmHistoryNavigation);
    }, [active, message, location.pathname, location.search, location.hash]);

    useEffect(() => {
        if (!active) return;

        const confirmLinkNavigation = (event: MouseEvent) => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const target = event.target;
            if (!(target instanceof Element)) return;
            const link = target.closest<HTMLAnchorElement>('a[href]');
            if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

            const destination = new URL(link.href, window.location.href);
            if (destination.origin !== window.location.origin) return;
            const current = new URL(window.location.href);
            if (destination.pathname === current.pathname && destination.search === current.search) return;
            if (isTabOnlyChange(current, destination)) return;

            if (!window.confirm(message)) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        };

        document.addEventListener('click', confirmLinkNavigation, true);
        return () => document.removeEventListener('click', confirmLinkNavigation, true);
    }, [active, message]);

    return useCallback(() => !active || window.confirm(message), [active, message]);
}

function isTabOnlyChange(current: URL, destination: URL): boolean {
    if (current.origin !== destination.origin || current.pathname !== destination.pathname || current.hash !== destination.hash) {
        return false;
    }

    const currentParams = new URLSearchParams(current.search);
    const destinationParams = new URLSearchParams(destination.search);
    currentParams.delete('tab');
    destinationParams.delete('tab');
    currentParams.sort();
    destinationParams.sort();

    return currentParams.toString() === destinationParams.toString()
        && new URLSearchParams(current.search).get('tab') !== new URLSearchParams(destination.search).get('tab');
}
