import { useState, useMemo, useCallback } from 'react';
import { NavigationIcon } from '@/app/navigation/NavigationIcon';
import type { NavigationIconName, NavigationSection } from '@/app/navigation/navigationTypes';
import { tenantWorkspaceNavigationSections } from '@/app/navigation/tenantWorkspaceNavigation';
import { filterNavigation } from '@/app/navigation/navigationUtils';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import {
    useQuickAccessConfig,
    QUICK_ACCESS_ICONS,
    getAvailablePresets,
    normalizePath,
    isQuickAccessItemAccessible,
    type QuickAccessItemConfig,
} from './quickAccessService';

interface ItemFormState {
    id: string;
    label: string;
    to: string;
    group: string;
    icon: NavigationIconName;
    enabled: boolean;
}

const EMPTY_FORM: ItemFormState = {
    id: '',
    label: '',
    to: '',
    group: '',
    icon: 'list',
    enabled: true,
};

function useAuthSafe() {
    try {
        return useAuth();
    } catch {
        return null;
    }
}

export default function QuickAccessSettingsPage() {
    const auth = useAuthSafe();
    const {
        items,
        rowVersion,
        isDefault,
        isLoading,
        isSaving,
        error: configError,
        updateItems,
        resetToDefaults,
        refreshFromServer,
    } = useQuickAccessConfig();

    const [modalOpen, setModalOpen] = useState(false);
    const [editingId, setEditingId] = useState<string | null>(null);
    const [selectedPreset, setSelectedPreset] = useState<string>('');
    const [formData, setFormData] = useState<ItemFormState>(EMPTY_FORM);
    const [formErrors, setFormErrors] = useState<Partial<Record<keyof ItemFormState, string>>>({});
    const [feedbackMessage, setFeedbackMessage] = useState<string | null>(null);
    const [feedbackType, setFeedbackType] = useState<'success' | 'error'>('success');
    const { confirm, confirmDialog } = useConfirmDialog();

    // Drag-and-drop state
    const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
    const [dragOverIndex, setDragOverIndex] = useState<number | null>(null);

    // Filtered workspace navigation sections based on current user permissions and modules
    const permittedSections: NavigationSection[] = useMemo(() => {
        if (!auth) return tenantWorkspaceNavigationSections;
        return filterNavigation(tenantWorkspaceNavigationSections, {
            tenantId: auth.tenant?.id ?? null,
            isPlatformOperator: auth.isPlatformOperator,
            organizationUnitId: auth.organizationUnit?.id ?? null,
            roles: auth.roles,
            permissions: auth.permissions,
            permissionsLoaded: auth.permissionsLoaded,
            enabledModules: auth.enabledModules,
            enabledModulesLoaded: auth.enabledModulesLoaded,
        });
    }, [auth]);

    // Dynamically available presets from all registered modules
    const availablePresets = useMemo(() => {
        return getAvailablePresets(permittedSections);
    }, [permittedSections]);

    const isItemAccessible = useCallback((item: QuickAccessItemConfig): boolean => {
        return isQuickAccessItemAccessible(item, permittedSections);
    }, [permittedSections]);

    const visibleCount = items.filter((item) => item.enabled && isItemAccessible(item)).length;
    const hiddenCount = items.filter((item) => !item.enabled).length;
    const restrictedCount = items.filter((item) => item.enabled && !isItemAccessible(item)).length;

    const showFeedback = (message: string, type: 'success' | 'error' = 'success') => {
        setFeedbackMessage(message);
        setFeedbackType(type);
        setTimeout(() => setFeedbackMessage(null), 5000);
    };

    const handleOpenAdd = () => {
        setEditingId(null);
        setSelectedPreset('');
        setFormData({
            id: `quick-access-${Date.now()}`,
            label: '',
            to: '',
            group: 'General',
            icon: 'list',
            enabled: true,
        });
        setFormErrors({});
        setModalOpen(true);
    };

    const handleOpenEdit = (item: QuickAccessItemConfig) => {
        setEditingId(item.id);
        setSelectedPreset('');
        setFormData({
            id: item.id,
            label: item.label,
            to: item.to,
            group: item.group,
            icon: item.icon,
            enabled: item.enabled,
        });
        setFormErrors({});
        setModalOpen(true);
    };

    const handlePresetSelect = (presetId: string) => {
        setSelectedPreset(presetId);
        const found = availablePresets.find((p) => p.id === presetId);
        if (!found) return;
        setFormData((prev) => ({
            ...prev,
            id: found.id,
            label: found.label,
            to: found.to,
            group: found.group,
            icon: found.icon,
        }));
        setFormErrors({});
    };

    const handleToggleEnabled = async (itemId: string) => {
        const next = items.map((item) => (item.id === itemId ? { ...item, enabled: !item.enabled } : item));
        const res = await updateItems(next);
        if (res.conflict) {
            showFeedback(res.message || 'Conflict detected; refreshed with latest settings.', 'error');
        } else {
            showFeedback('Updated menu visibility.');
        }
    };

    const handleMove = async (index: number, direction: 'up' | 'down') => {
        const targetIndex = direction === 'up' ? index - 1 : index + 1;
        if (targetIndex < 0 || targetIndex >= items.length) return;

        const next = [...items];
        const temp = next[index];
        next[index] = next[targetIndex];
        next[targetIndex] = temp;

        const res = await updateItems(next);
        if (res.conflict) {
            showFeedback(res.message || 'Conflict detected; refreshed with latest settings.', 'error');
        } else {
            showFeedback('Reordered quick access shortcuts.');
        }
    };

    // Drag and Drop handlers
    const handleDragStart = (index: number, e: React.DragEvent) => {
        setDraggedIndex(index);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(index));
    };

    const handleDragOver = (index: number, e: React.DragEvent) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (dragOverIndex !== index) {
            setDragOverIndex(index);
        }
    };

    const handleDrop = async (targetIndex: number, e: React.DragEvent) => {
        e.preventDefault();
        if (draggedIndex === null || draggedIndex === targetIndex) {
            setDraggedIndex(null);
            setDragOverIndex(null);
            return;
        }

        const next = [...items];
        const [moved] = next.splice(draggedIndex, 1);
        next.splice(targetIndex, 0, moved);

        setDraggedIndex(null);
        setDragOverIndex(null);

        const res = await updateItems(next);
        if (res.conflict) {
            showFeedback(res.message || 'Conflict detected; refreshed with latest settings.', 'error');
        } else {
            showFeedback('Reordered quick access shortcuts via drag-and-drop.');
        }
    };

    const handleDragEnd = () => {
        setDraggedIndex(null);
        setDragOverIndex(null);
    };

    const handleDelete = async (item: QuickAccessItemConfig) => {
        const confirmed = await confirm({
            title: 'Delete Quick Access Shortcut',
            message: `Are you sure you want to delete "${item.label}"? You can re-add it or reset to defaults at any time.`,
            confirmLabel: 'Delete',
            danger: true,
        });
        if (!confirmed) return;

        const next = items.filter((i) => i.id !== item.id);
        const res = await updateItems(next);
        if (res.conflict) {
            showFeedback(res.message || 'Conflict detected; refreshed with latest settings.', 'error');
        } else {
            showFeedback(`Removed "${item.label}" from Quick Access.`);
        }
    };

    const handleReset = async () => {
        const confirmed = await confirm({
            title: 'Reset to Default Shortcuts',
            message: 'Are you sure you want to restore the default Quick Access menu? Your custom items and order adjustments will be replaced with standard presets.',
            confirmLabel: 'Reset Defaults',
            danger: false,
        });
        if (!confirmed) return;

        const res = await resetToDefaults();
        if (res.success || !res.message?.includes('409')) {
            showFeedback('Reset Quick Access menu to default settings.');
        } else {
            showFeedback(res.message || 'Failed to reset settings.', 'error');
        }
    };

    const handleSaveForm = async (e: React.FormEvent) => {
        e.preventDefault();
        const errors: Partial<Record<keyof ItemFormState, string>> = {};

        const trimmedLabel = formData.label.trim();
        const trimmedTo = formData.to.trim();
        const trimmedGroup = formData.group.trim();

        if (!trimmedLabel) {
            errors.label = 'Label is required.';
        }
        if (!trimmedTo) {
            errors.to = 'Route path is required.';
        } else if (!trimmedTo.startsWith('/') || trimmedTo.startsWith('//') || trimmedTo.startsWith('/\\')) {
            errors.to = 'Route must start with a single forward slash (/), e.g. /customers.';
        } else {
            const normalizedTo = normalizePath(trimmedTo);
            const isDuplicate = items.some(
                (item) => item.id !== editingId && normalizePath(item.to) === normalizedTo
            );
            if (isDuplicate) {
                errors.to = 'A shortcut with this route path already exists.';
            }
        }
        if (!trimmedGroup) {
            errors.group = 'Group name is required.';
        }

        if (Object.keys(errors).length > 0) {
            setFormErrors(errors);
            return;
        }

        // Ensure ID is strictly unique among existing items
        let resolvedId = editingId ? formData.id : (formData.id || `quick-access-${Date.now()}`);
        const isDuplicateId = items.some((i) => (editingId ? i.id !== editingId : true) && i.id === resolvedId);
        if (isDuplicateId) {
            resolvedId = `${resolvedId}-${Date.now()}`;
        }

        const cleanItem: QuickAccessItemConfig = {
            id: resolvedId,
            label: trimmedLabel,
            to: normalizePath(trimmedTo),
            group: trimmedGroup,
            icon: formData.icon,
            enabled: formData.enabled,
            order: editingId ? (items.find((i) => i.id === editingId)?.order ?? items.length + 1) : items.length + 1,
        };

        let next: QuickAccessItemConfig[];
        if (editingId) {
            next = items.map((item) => (item.id === editingId ? cleanItem : item));
        } else {
            next = [...items, cleanItem];
        }

        const res = await updateItems(next);
        setModalOpen(false);

        if (res.conflict) {
            showFeedback(res.message || 'Conflict detected; refreshed with latest settings.', 'error');
        } else {
            showFeedback(editingId ? `Updated shortcut "${cleanItem.label}".` : `Added new shortcut "${cleanItem.label}".`);
        }
    };

    // Live preview: only displays enabled AND accessible items, grouped by configured category
    const previewGroups = useMemo(() => {
        return items
            .filter((item) => item.enabled && isItemAccessible(item))
            .reduce<Array<{ label: string; links: QuickAccessItemConfig[] }>>((acc, item) => {
                let grp = acc.find((g) => g.label === item.group);
                if (!grp) {
                    grp = { label: item.group, links: [] };
                    acc.push(grp);
                }
                grp.links.push(item);
                return acc;
            }, []);
    }, [items, isItemAccessible]);

    const iconSelectOptions = QUICK_ACCESS_ICONS.map((icon) => ({
        value: icon,
        label: icon.charAt(0).toUpperCase() + icon.slice(1),
    }));

    const presetSelectOptions = [
        { value: '', label: 'Select a registered module page to auto-fill...' },
        ...availablePresets.map((preset) => ({
            value: preset.id,
            label: `${preset.group} > ${preset.label} (${preset.to})`,
        })),
    ];

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Quick Access Settings"
                description="Manage and customize the shortcuts in your header Quick Access dropdown. Easily enable, reorder via drag-and-drop, edit, or create one-click navigation links."
                actions={(
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={handleReset}
                            disabled={isSaving}
                        >
                            <svg className="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                <path d="M3 3v5h5" />
                            </svg>
                            <span>Reset Defaults</span>
                        </Button>
                        <Button
                            type="button"
                            variant="primary"
                            onClick={handleOpenAdd}
                            disabled={isSaving}
                        >
                            <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <path d="M12 5v14m-7-7h14" />
                            </svg>
                            <span>Add Shortcut</span>
                        </Button>
                    </div>
                )}
            />

            {feedbackMessage && (
                feedbackType === 'error' ? (
                    <div className="flex items-center justify-between rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                        <span>{feedbackMessage}</span>
                        <button type="button" onClick={() => setFeedbackMessage(null)} className="text-xs font-semibold underline ml-4 cursor-pointer">
                            Dismiss
                        </button>
                    </div>
                ) : (
                    <SuccessAlert message={feedbackMessage} onDismiss={() => setFeedbackMessage(null)} />
                )
            )}

            {configError && !feedbackMessage && (
                <div className="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800" role="alert">
                    <span>{configError}</span>
                    <button type="button" onClick={() => refreshFromServer()} className="text-xs font-semibold underline ml-4 cursor-pointer">
                        Retry Sync
                    </button>
                </div>
            )}

            {/* Status summary banner */}
            <div className="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-blue-200/80 bg-blue-50/60 p-4 text-sm text-slate-700">
                <div className="flex flex-wrap items-center gap-3">
                    <span className="font-semibold text-slate-900">Configured Shortcuts:</span>
                    <span className="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs border border-slate-200">
                        Total: <span className="font-bold text-slate-900">{items.length}</span>
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 shadow-2xs border border-emerald-200">
                        Visible: <span className="font-bold text-emerald-900">{visibleCount}</span>
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 shadow-2xs border border-slate-200">
                        Hidden: <span className="font-bold text-slate-800">{hiddenCount}</span>
                    </span>
                    {restrictedCount > 0 && (
                        <span className="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 shadow-2xs border border-amber-200" title="Shortcuts enabled in configuration but hidden from menu because the module or route is restricted in current workspace">
                            Restricted: <span className="font-bold text-amber-900">{restrictedCount}</span>
                        </span>
                    )}
                    {isSaving && (
                        <span className="inline-flex items-center gap-1.5 text-xs text-blue-600 font-medium">
                            <svg className="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Saving to server...
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-2 text-xs text-slate-500">
                    <span className="inline-block h-2 w-2 rounded-full bg-emerald-500" />
                    <span>Persisted in Database & cached in LocalStorage. Dropdown reflects changes immediately.</span>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Main Table: 2 cols on desktop */}
                <div className="lg:col-span-2">
                    <Panel
                        title="Shortcut Entries"
                        description="Drag rows by the handle to reorder, toggle visibility, or edit shortcuts."
                    >
                        {items.length === 0 ? (
                            <div className="py-12 text-center">
                                <svg className="mx-auto h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="m13 2-8 11h6l-1 9 9-12h-6l1-8Z" />
                                </svg>
                                <h3 className="mt-2 text-sm font-semibold text-slate-800">No shortcuts configured</h3>
                                <p className="mt-1 text-xs text-slate-500">Restore the default shortcuts to get started.</p>
                                <div className="mt-4">
                                    <Button variant="secondary" size="sm" onClick={handleReset}>
                                        Restore Defaults
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-lg border border-slate-200">
                                <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                    <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        <tr>
                                            <th scope="col" className="w-12 px-2 py-3 text-center" title="Drag to reorder">Order</th>
                                            <th scope="col" className="px-4 py-3">Shortcut</th>
                                            <th scope="col" className="px-3 py-3">Group</th>
                                            <th scope="col" className="px-3 py-3">Route</th>
                                            <th scope="col" className="px-3 py-3 text-center">Visibility</th>
                                            <th scope="col" className="px-4 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {items.map((item, index) => {
                                            const accessible = isItemAccessible(item);
                                            const isDragging = draggedIndex === index;
                                            const isOver = dragOverIndex === index;

                                            return (
                                                <tr
                                                    key={item.id}
                                                    draggable
                                                    onDragStart={(e) => handleDragStart(index, e)}
                                                    onDragOver={(e) => handleDragOver(index, e)}
                                                    onDrop={(e) => handleDrop(index, e)}
                                                    onDragEnd={handleDragEnd}
                                                    className={`transition-colors ${
                                                        isDragging ? 'opacity-40 bg-slate-100' : 'hover:bg-slate-50/80'
                                                    } ${isOver ? 'border-t-2 border-blue-500 bg-blue-50/40' : ''}`}
                                                >
                                                    {/* Reorder: Drag handle + accessible up/down buttons */}
                                                    <td className="px-2 py-3 text-center whitespace-nowrap">
                                                        <div className="flex items-center justify-center gap-1">
                                                            <span
                                                                className="inline-flex h-7 w-6 cursor-grab items-center justify-center rounded text-slate-400 hover:bg-slate-200 hover:text-slate-700 active:cursor-grabbing"
                                                                title="Drag to reorder"
                                                                aria-label={`Drag ${item.label} to reorder`}
                                                            >
                                                                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                                    <circle cx="9" cy="6" r="1.5" />
                                                                    <circle cx="15" cy="6" r="1.5" />
                                                                    <circle cx="9" cy="12" r="1.5" />
                                                                    <circle cx="15" cy="12" r="1.5" />
                                                                    <circle cx="9" cy="18" r="1.5" />
                                                                    <circle cx="15" cy="18" r="1.5" />
                                                                </svg>
                                                            </span>
                                                            <div className="inline-flex flex-col gap-0.5">
                                                                <button
                                                                    type="button"
                                                                    disabled={index === 0}
                                                                    onClick={() => handleMove(index, 'up')}
                                                                    className="inline-flex h-4 w-4 items-center justify-center rounded text-slate-400 hover:bg-slate-200 hover:text-slate-800 disabled:opacity-20 disabled:pointer-events-none"
                                                                    aria-label={`Move ${item.label} up`}
                                                                    title="Move up"
                                                                >
                                                                    <svg className="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                                        <path fillRule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clipRule="evenodd" />
                                                                    </svg>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    disabled={index === items.length - 1}
                                                                    onClick={() => handleMove(index, 'down')}
                                                                    className="inline-flex h-4 w-4 items-center justify-center rounded text-slate-400 hover:bg-slate-200 hover:text-slate-800 disabled:opacity-20 disabled:pointer-events-none"
                                                                    aria-label={`Move ${item.label} down`}
                                                                    title="Move down"
                                                                >
                                                                    <svg className="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                                        <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clipRule="evenodd" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    {/* Icon + Label */}
                                                    <td className="px-4 py-3">
                                                        <div className="flex items-center gap-2.5">
                                                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 border border-blue-200/60">
                                                                <NavigationIcon name={item.icon} className="h-4 w-4" />
                                                            </span>
                                                            <div className="flex flex-col">
                                                                <span className="font-medium text-slate-900">{item.label}</span>
                                                                {!accessible && (
                                                                    <span className="inline-flex items-center text-[11px] font-medium text-amber-600" title="Hidden from dropdown because your role/plan lacks access to this module">
                                                                        Restricted in workspace
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>

                                                    {/* Group */}
                                                    <td className="px-3 py-3 whitespace-nowrap">
                                                        <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                            {item.group}
                                                        </span>
                                                    </td>

                                                    {/* Route */}
                                                    <td className="px-3 py-3">
                                                        <code className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700">
                                                            {item.to}
                                                        </code>
                                                    </td>

                                                    {/* Visibility Toggle */}
                                                    <td className="px-3 py-3 text-center whitespace-nowrap">
                                                        <button
                                                            type="button"
                                                            role="switch"
                                                            aria-checked={item.enabled}
                                                            aria-label={`Toggle visibility for ${item.label}`}
                                                            onClick={() => handleToggleEnabled(item.id)}
                                                            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold cursor-pointer transition-colors ${
                                                                item.enabled
                                                                    ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                                            }`}
                                                        >
                                                            <span className={`h-2 w-2 rounded-full ${item.enabled ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                                                            <span>{item.enabled ? 'Visible' : 'Hidden'}</span>
                                                        </button>
                                                    </td>

                                                    {/* Actions */}
                                                    <td className="px-4 py-3 text-right whitespace-nowrap">
                                                        <div className="inline-flex items-center gap-1.5">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-8 px-2 text-slate-600 hover:text-blue-600"
                                                                onClick={() => handleOpenEdit(item)}
                                                                aria-label={`Edit ${item.label}`}
                                                            >
                                                                Edit
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-8 px-2 text-slate-500 hover:text-rose-600"
                                                                onClick={() => handleDelete(item)}
                                                                aria-label={`Delete ${item.label}`}
                                                            >
                                                                Delete
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Panel>
                </div>

                {/* Right Column: Live Preview Panel */}
                <div className="space-y-6">
                    <Panel
                        title="Live Dropdown Preview"
                        description="Simulated preview of how your Quick Access dropdown appears in the header."
                    >
                        <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-md">
                            <div className="flex items-center justify-between border-b border-slate-100 px-2 pb-2">
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick access</p>
                                <span className="text-[11px] text-slate-400">Live Preview</span>
                            </div>

                            {previewGroups.length === 0 ? (
                                <div className="py-6 text-center text-xs text-slate-400">
                                    No shortcuts are currently enabled and accessible. The menu in the header will be hidden.
                                </div>
                            ) : (
                                <div className="space-y-3 pt-2">
                                    {previewGroups.map((group, groupIdx) => (
                                        <div key={group.label} className={groupIdx > 0 ? 'border-t border-slate-100 pt-2' : ''}>
                                            <p className="px-2 pb-1 text-xs font-bold text-slate-900">{group.label}</p>
                                            <div className="space-y-0.5">
                                                {group.links.map((link) => (
                                                    <div
                                                        key={link.id}
                                                        className="flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-xs font-medium text-slate-800 transition-colors hover:bg-blue-50 hover:text-blue-700"
                                                    >
                                                        <NavigationIcon name={link.icon} className="h-4 w-4 shrink-0 text-blue-600" />
                                                        <span className="truncate">{link.label}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </Panel>

                    <Panel title="Configuration Guidance">
                        <ul className="space-y-2 text-xs leading-relaxed text-slate-600">
                            <li className="flex items-start gap-2">
                                <span className="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600" />
                                <span><strong>Drag & Drop Reordering:</strong> Drag any row using the grip handle on the left to customize dropdown priority instantly.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600" />
                                <span><strong>Permissions Guard:</strong> Shortcuts are filtered by role permissions and module access. Inaccessible destinations are never exposed.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600" />
                                <span><strong>Database Persistence:</strong> Preferences are persisted in the database as the source of truth, with LocalStorage caching for instantaneous menu rendering.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600" />
                                <span><strong>Fast Customization:</strong> Click &quot;Customize&quot; at the top of the header Quick Access dropdown at any time to return to this screen.</span>
                            </li>
                        </ul>
                    </Panel>
                </div>
            </div>

            {/* Add / Edit Modal */}
            <Modal
                open={modalOpen}
                title={editingId ? 'Edit Quick Access Shortcut' : 'Add Quick Access Shortcut'}
                onClose={() => setModalOpen(false)}
            >
                <form noValidate onSubmit={handleSaveForm} className="space-y-4">
                    {!editingId && (
                        <div className="rounded-lg border border-slate-200 bg-slate-50/70 p-3">
                            <Select
                                label="Quick Presets"
                                hint="Optional: select any registered page from existing modules to auto-fill details."
                                options={presetSelectOptions}
                                placeholder={null}
                                value={selectedPreset}
                                onChange={(e) => handlePresetSelect(e.target.value)}
                            />
                        </div>
                    )}

                    <Input
                        label="Label / Title"
                        required
                        value={formData.label}
                        onChange={(e) => setFormData({ ...formData, label: e.target.value })}
                        error={formErrors.label}
                        placeholder="e.g. Create Service Job"
                    />

                    <Input
                        label="Route / URL Path"
                        required
                        value={formData.to}
                        onChange={(e) => setFormData({ ...formData, to: e.target.value })}
                        error={formErrors.to}
                        placeholder="e.g. /vehicle-service/jobs/create"
                        hint="Must start with a leading slash (/)."
                    />

                    <Input
                        label="Group Name"
                        required
                        value={formData.group}
                        onChange={(e) => setFormData({ ...formData, group: e.target.value })}
                        error={formErrors.group}
                        placeholder="e.g. Vehicle Service, Selling, Master Data"
                        hint="Shortcuts with the same group name are grouped together in the dropdown."
                    />

                    <Select
                        label="Icon"
                        options={iconSelectOptions}
                        value={formData.icon}
                        onChange={(e) => setFormData({ ...formData, icon: e.target.value as NavigationIconName })}
                        placeholder={null}
                    />

                    <div className="flex items-center gap-3 pt-2">
                        <input
                            type="checkbox"
                            id="form-item-enabled"
                            checked={formData.enabled}
                            onChange={(e) => setFormData({ ...formData, enabled: e.target.checked })}
                            className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label htmlFor="form-item-enabled" className="text-sm font-medium text-slate-700">
                            Visible in Quick Access dropdown
                        </label>
                    </div>

                    <div className="flex justify-end gap-2.5 border-t border-slate-100 pt-4">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setModalOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="primary"
                            disabled={isSaving}
                        >
                            {editingId ? 'Save Changes' : 'Save Shortcut'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {confirmDialog}
        </div>
    );
}
