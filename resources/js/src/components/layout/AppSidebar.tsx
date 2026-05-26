import { useEffect, useMemo, useState } from 'react';
import { NavLink } from 'react-router-dom';
import { appNavigationSections, type AppPageMeta } from '../../app/router/app-navigation';
import { cn } from '../../lib/cn';
import { AppIcon } from './AppIcons';

type AppSidebarProps = {
    currentPage: AppPageMeta | null;
    isOpen: boolean;
    onClose: () => void;
};

export function AppSidebar({ currentPage, isOpen, onClose }: AppSidebarProps) {
    const activeSectionId = currentPage?.sectionId ?? 'dashboard';
    const [expandedSections, setExpandedSections] = useState<string[]>(() => [activeSectionId]);

    useEffect(() => {
        if (!expandedSections.includes(activeSectionId)) {
            setExpandedSections((current) => [...current, activeSectionId]);
        }
    }, [activeSectionId, expandedSections]);

    const navigationGroups = useMemo(() => appNavigationSections, []);

    function toggleSection(sectionId: string) {
        setExpandedSections((current) =>
            current.includes(sectionId) ? current.filter((item) => item !== sectionId) : [...current, sectionId],
        );
    }

    return (
        <>
            <div
                aria-hidden={!isOpen}
                className={cn(
                    'fixed inset-0 z-30 bg-stone-950/25 backdrop-blur-sm transition lg:hidden',
                    isOpen ? 'opacity-100' : 'pointer-events-none opacity-0',
                )}
                onClick={onClose}
            />

            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-40 flex w-[18.5rem] max-w-[88vw] flex-col border-r border-stone-200/80 bg-white/95 px-4 py-5 shadow-xl shadow-stone-950/10 backdrop-blur transition duration-200 lg:static lg:z-auto lg:w-[18rem] lg:translate-x-0 lg:shadow-none',
                    isOpen ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                <div className="flex items-center justify-between gap-3 border-b border-stone-200/80 px-2 pb-4">
                    <div className="space-y-1">
                        <p className="text-xs font-medium uppercase tracking-[0.32em] text-stone-500">AutoERP</p>
                        <p className="text-sm text-stone-600">Operations workspace</p>
                    </div>

                    <button
                        className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-stone-200 text-stone-500 transition hover:bg-stone-50 lg:hidden"
                        onClick={onClose}
                        type="button"
                    >
                        <svg
                            aria-hidden="true"
                            className="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path d="m18 6-12 12" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <nav className="mt-4 flex-1 space-y-1 overflow-y-auto pr-1">
                    {navigationGroups.map((section) => {
                        const hasChildren = Boolean(section.children?.length);
                        const isExpanded = expandedSections.includes(section.id);
                        const isActive = section.id === activeSectionId;

                        if (!hasChildren && section.path) {
                            return (
                                <NavLink
                                    key={section.id}
                                    className={({ isActive: isCurrent }) =>
                                        cn(
                                            'group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition',
                                            isCurrent
                                                ? 'bg-stone-950 text-white shadow-sm'
                                                : 'text-stone-700 hover:bg-stone-100/80 hover:text-stone-950',
                                        )
                                    }
                                    end
                                    onClick={onClose}
                                    to={section.path}
                                >
                                    <AppIcon className="h-[1.05rem] w-[1.05rem] shrink-0" name={section.icon} />
                                    <span className="min-w-0 flex-1 truncate">{section.label}</span>
                                </NavLink>
                            );
                        }

                        return (
                            <div key={section.id}>
                                <button
                                    className={cn(
                                        'flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left text-sm font-medium transition',
                                        isActive
                                            ? 'bg-stone-100 text-stone-950'
                                            : 'text-stone-700 hover:bg-stone-100/80 hover:text-stone-950',
                                    )}
                                    onClick={() => toggleSection(section.id)}
                                    type="button"
                                >
                                    <AppIcon className="h-[1.05rem] w-[1.05rem] shrink-0" name={section.icon} />
                                    <span className="min-w-0 flex-1 truncate">{section.label}</span>
                                    <svg
                                        aria-hidden="true"
                                        className={cn('h-4 w-4 shrink-0 transition duration-200', isExpanded && 'rotate-180')}
                                        fill="none"
                                        stroke="currentColor"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>

                                <div
                                    className={cn(
                                        'grid overflow-hidden px-2 transition-all duration-200',
                                        isExpanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-70',
                                    )}
                                >
                                    <div className="overflow-hidden">
                                        <div className="space-y-1 pb-2 pt-1">
                                            {section.children?.map((child) => {
                                                const isChildActive = currentPage?.path === child.path;

                                                return (
                                                    <NavLink
                                                        key={child.id}
                                                        className={cn(
                                                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition',
                                                            isChildActive
                                                                ? 'bg-stone-950 text-white shadow-sm'
                                                                : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900',
                                                        )}
                                                        onClick={onClose}
                                                        to={child.path}
                                                    >
                                                        <span
                                                            className={cn(
                                                                'h-1.5 w-1.5 rounded-full bg-current opacity-65',
                                                                isChildActive && 'opacity-100',
                                                            )}
                                                        />
                                                        <span className="min-w-0 flex-1 truncate">{child.label}</span>
                                                    </NavLink>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </nav>
            </aside>
        </>
    );
}
