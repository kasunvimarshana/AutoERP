import { useEffect, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import { createPortal } from 'react-dom';
import { useNavigate } from 'react-router-dom';
import type { NavigationItem, NavigationSection } from './navigationTypes';
import { flattenDestinations } from './navigationUtils';
import { useFocusTrap } from './useFocusTrap';

export function NavigationPalette({
    open,
    sections,
    actions,
    onClose,
}: {
    open: boolean;
    sections: NavigationSection[];
    actions: NavigationItem[];
    onClose: () => void;
}) {
    const navigate = useNavigate();
    const trapRef = useFocusTrap<HTMLDivElement>(open, onClose);
    const inputRef = useRef<HTMLInputElement>(null);
    const [query, setQuery] = useState('');
    const [activeIndex, setActiveIndex] = useState(0);
    const entries = useMemo(() => {
        const normalized = query.trim().toLowerCase();
        const workspaces = flattenDestinations(sections).map((item) => ({ ...item, kind: 'Workspace' }));
        const commands = actions.map((item) => ({ ...item, kind: 'Action' }));
        return [...workspaces, ...commands]
            .filter((item) => item.route && (!normalized || item.label.toLowerCase().includes(normalized)))
            .slice(0, 12);
    }, [actions, query, sections]);

    useEffect(() => {
        if (!open) {
            setQuery('');
            setActiveIndex(0);
            return;
        }
        window.requestAnimationFrame(() => inputRef.current?.focus());
    }, [open]);

    useEffect(() => setActiveIndex(0), [query]);

    if (!open) return null;

    const select = (item: NavigationItem | undefined) => {
        if (!item?.route) return;
        navigate(item.route);
        onClose();
    };
    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((index) => entries.length ? (index + 1) % entries.length : 0);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((index) => entries.length ? (index - 1 + entries.length) % entries.length : 0);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            select(entries[activeIndex]);
        }
    };

    return createPortal(
        <div
            className="fixed inset-0 z-[70] flex items-start justify-center bg-slate-950/55 px-4 pt-[12vh]"
            role="presentation"
            onMouseDown={(event) => event.target === event.currentTarget && onClose()}
        >
            <div
                ref={trapRef}
                role="dialog"
                aria-modal="true"
                aria-label="Navigation search"
                className="w-full max-w-xl overflow-hidden rounded-lg border border-slate-300 bg-white shadow-2xl"
            >
                <label className="sr-only" htmlFor="navigation-search">Search workspaces and actions</label>
                <input
                    ref={inputRef}
                    id="navigation-search"
                    role="combobox"
                    aria-expanded="true"
                    aria-controls="navigation-search-results"
                    aria-activedescendant={entries[activeIndex] ? `navigation-result-${entries[activeIndex].id}` : undefined}
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    onKeyDown={onKeyDown}
                    placeholder="Search workspaces and actions"
                    className="h-14 w-full border-0 border-b border-slate-200 px-4 text-base outline-none focus:ring-2 focus:ring-inset focus:ring-sky-500"
                />
                <div id="navigation-search-results" role="listbox" className="max-h-[55vh] overflow-y-auto p-2">
                    {entries.length === 0 ? (
                        <p className="px-3 py-8 text-center text-sm text-slate-500">No permitted destination found.</p>
                    ) : entries.map((item, index) => (
                        <button
                            key={`${item.kind}-${item.id}`}
                            id={`navigation-result-${item.id}`}
                            type="button"
                            role="option"
                            aria-selected={index === activeIndex}
                            onMouseEnter={() => setActiveIndex(index)}
                            onClick={() => select(item)}
                            className={`flex min-h-12 w-full items-center justify-between rounded-md px-3 text-left text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 ${
                                index === activeIndex ? 'bg-sky-50 text-sky-900' : 'text-slate-700 hover:bg-slate-50'
                            }`}
                        >
                            <span className="font-medium">{item.label}</span>
                            <span className="text-xs uppercase text-slate-400">{item.kind}</span>
                        </button>
                    ))}
                </div>
            </div>
        </div>,
        document.body,
    );
}

