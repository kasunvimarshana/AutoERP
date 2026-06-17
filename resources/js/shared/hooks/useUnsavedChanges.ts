import { useCallback, useEffect } from 'react';
import { useBeforeUnload } from 'react-router-dom';

const defaultMessage = 'You have unsaved changes. Leave this page and discard them?';

export function useUnsavedChanges(active: boolean, message = defaultMessage) {
    useBeforeUnload(useCallback((event) => {
        if (!active) return;
        event.preventDefault();
        event.returnValue = '';
    }, [active]));

    useEffect(() => {
        if (!active) return;

        const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
        const confirmHistoryNavigation = () => {
            if (window.confirm(message)) return;

            window.history.pushState(null, '', currentUrl);
        };

        window.addEventListener('popstate', confirmHistoryNavigation);
        return () => window.removeEventListener('popstate', confirmHistoryNavigation);
    }, [active, message]);

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
            if (destination.pathname === window.location.pathname && destination.search === window.location.search) return;

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
