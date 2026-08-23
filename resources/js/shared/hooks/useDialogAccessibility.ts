import { useEffect, useEffectEvent, type RefObject } from 'react';

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

export function useDialogAccessibility(
    open: boolean,
    containerRef: RefObject<HTMLElement | null>,
    onRequestClose: () => void,
) {
    const requestClose = useEffectEvent(onRequestClose);

    useEffect(() => {
        if (!open) return;

        const previouslyFocused = document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const focusable = () => Array.from(
            containerRef.current?.querySelectorAll<HTMLElement>(focusableSelector) ?? [],
        ).filter((element) => !element.hidden && element.getAttribute('aria-hidden') !== 'true');

        window.requestAnimationFrame(() => {
            const activeElement = document.activeElement;
            if (activeElement instanceof HTMLElement && containerRef.current?.contains(activeElement)) {
                return;
            }
            const first = focusable()[0] ?? containerRef.current;
            first?.focus();
        });

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                requestClose();
                return;
            }
            if (event.key !== 'Tab') return;

            const elements = focusable();
            if (elements.length === 0) {
                event.preventDefault();
                containerRef.current?.focus();
                return;
            }

            const first = elements[0];
            const last = elements[elements.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        window.addEventListener('keydown', onKeyDown);
        return () => {
            window.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = previousOverflow;
            previouslyFocused?.focus();
        };
    }, [containerRef, open]);
}
