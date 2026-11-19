import { useState, useId } from 'react';
import { NavigationIcon } from '@/app/navigation/NavigationIcon';
import type { NavigationIconName } from '@/app/navigation/navigationTypes';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { ConfirmDialog, useConfirmDialog } from '@/shared/components/ConfirmDialog';
import {
    useQuickAccessConfig,
    QUICK_ACCESS_ICONS,
    SYSTEM_PRESET_ITEMS,
    normalizePath,
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

export default function QuickAccessSettingsPage() {
    const { items, updateItems, resetToDefaults } = useQuickAccessConfig();
    const [modalOpen, setModalOpen] = useState(false);
    const [editingId, setEditingId] = useState<string | null>(null);
    const [selectedPreset, setSelectedPreset] = useState<string>('');
    const [formData, setFormData] = useState<ItemFormState>(EMPTY_FORM);
    const [formErrors, setFormErrors] = useState<Partial<Record<keyof ItemFormState, string>>>({});
    const [feedbackMessage, setFeedbackMessage] = useState<string | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();

    const enabledCount = items.filter((item) => item.enabled).length;
    const disabledCount = items.length - enabledCount;

    const showFeedback = (message: string) => {
        setFeedbackMessage(message);
        setTimeout(() => setFeedbackMessage(null), 4000);
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
        const found = SYSTEM_PRESET_ITEMS.find((p) => p.id === presetId);
        if (!found) return;
        setFormData((prev) => ({
            ...prev,
            label: found.label,
            to: found.to,
            group: found.group,
            icon: found.icon,
        }));
        setFormErrors({});
    };

    const handleToggleEnabled = (itemId: string) => {
        const next = items.map((item) => (item.id === itemId ? { ...item, enabled: !item.enabled } : item));
        updateItems(next);
        showFeedback('Updated menu visibility.');
    };

    const handleMove = (index: number, direction: 'up' | 'down') => {
        const targetIndex = direction === 'up' ? index - 1 : index + 1;
        if (targetIndex < 0 || targetIndex >= items.length) return;

        const next = [...items];
        const temp = next[index];
        next[index] = next[targetIndex];
        next[targetIndex] = temp;

        updateItems(next);
        showFeedback('Reordered quick access shortcuts.');
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
        updateItems(next);
        showFeedback(`Removed "${item.label}" from Quick Access.`);
    };

    const handleReset = async () => {
        const confirmed = await confirm({
            title: 'Reset to Default Shortcuts',
            message: 'Are you sure you want to restore the default Quick Access menu? Your custom items and order adjustments will be replaced with standard presets.',
            confirmLabel: 'Reset Defaults',
            danger: false,
        });
        if (!confirmed) return;

        resetToDefaults();
        showFeedback('Reset Quick Access menu to default settings.');
    };

    const handleSaveForm = (e: React.FormEvent) => {
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
        } else if (!trimmedTo.startsWith('/') || trimmedTo.startsWith('//')) {
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

        const cleanItem: QuickAccessItemConfig = {
            id: editingId ? formData.id : (formData.id || `quick-access-${Date.now()}`),
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
            showFeedback(`Updated shortcut "${cleanItem.label}".`);
        } else {
            next = [...items, cleanItem];
            showFeedback(`Added new shortcut "${cleanItem.label}".`);
        }

        updateItems(next);
        setModalOpen(false);
    };

    // Group enabled items for live preview
    const previewGroups = items
        .filter((item) => item.enabled)
        .reduce<Array<{ label: string; links: QuickAccessItemConfig[] }>>((acc, item) => {
            let grp = acc.find((g) => g.label === item.group);
            if (!grp) {
                grp = { label: item.group, links: [] };
                acc.push(grp);
            }
            grp.links.push(item);
            return acc;
        }, []);

    const iconSelectOptions = QUICK_ACCESS_ICONS.map((icon) => ({
        value: icon,
        label: icon.charAt(0).toUpperCase() + icon.slice(1),
    }));

    const presetSelectOptions = [
        { value: '', label: 'Select a common page to auto-fill...' },
        ...SYSTEM_PRESET_ITEMS.map((preset) => ({
            value: preset.id,
            label: `${preset.group} > ${preset.label} (${preset.to})`,
        })),
    ];

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Quick Access Settings"
                description="Manage and customize the shortcuts in your header Quick Access dropdown. Easily enable, reorder, edit, or create one-click navigation links."
                actions={(
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={handleReset}
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
                <SuccessAlert message={feedbackMessage} onDismiss={() => setFeedbackMessage(null)} />
            )}

            {/* Status summary banner */}
            <div className="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-blue-200/80 bg-blue-50/60 p-4 text-sm text-slate-700">
                <div className="flex flex-wrap items-center gap-4">
                    <span className="font-semibold text-slate-900">Configured Shortcuts:</span>
                    <span className="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs border border-slate-200">
                        Total: <span className="font-bold text-slate-900">{items.length}</span>
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 shadow-2xs border border-emerald-200">
                        Visible: <span className="font-bold text-emerald-900">{enabledCount}</span>
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 shadow-2xs border border-slate-200">
                        Hidden: <span className="font-bold text-slate-800">{disabledCount}</span>
                    </span>
                </div>
                <p className="text-xs text-slate-500">
                    Preferences are saved in your browser storage and update the header dropdown immediately.
                </p>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Main Table: 2 cols on desktop */}
                <div className="lg:col-span-2">
                    <Panel
                        title="Shortcut Entries"
                        description="Toggle items on or off, edit labels, or change their display order."
                    >
                        {items.length === 0 ? (
                            <div className="py-12 text-center">
                                <svg className="mx-auto h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="m13 2-8 11h6l-1 9 9-12h-6l1-8Z" />
                                </svg>
                                <h3 className="mt-2 text-sm font-semibold text-slate-800">No shortcuts configured</h3>
                                <p className="mt-1 text-xs text-slate-500">Restore the default shortcuts to get started.</p>
                                <div className="mt-4">
                                    <Button variant="secondary" size="sm" onClick={resetToDefaults}>
                                        Restore Defaults
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-lg border border-slate-200">
                                <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                    <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        <tr>
                                            <th scope="col" className="w-16 px-3 py-3 text-center">Order</th>
                                            <th scope="col" className="px-4 py-3">Shortcut</th>
                                            <th scope="col" className="px-3 py-3">Group</th>
                                            <th scope="col" className="px-3 py-3">Route</th>
                                            <th scope="col" className="px-3 py-3 text-center">Visibility</th>
                                            <th scope="col" className="px-4 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {items.map((item, index) => (
                                            <tr key={item.id} className="transition-colors hover:bg-slate-50/80">
                                                {/* Reorder Buttons */}
                                                <td className="px-3 py-3 text-center whitespace-nowrap">
                                                    <div className="inline-flex flex-col gap-0.5">
                                                        <button
                                                            type="button"
                                                            disabled={index === 0}
                                                            onClick={() => handleMove(index, 'up')}
                                                            className="inline-flex h-5 w-5 items-center justify-center rounded text-slate-500 hover:bg-slate-200 hover:text-slate-800 disabled:opacity-30 disabled:pointer-events-none"
                                                            aria-label={`Move ${item.label} up`}
                                                            title="Move up"
                                                        >
                                                            <svg className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fillRule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clipRule="evenodd" />
                                                            </svg>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            disabled={index === items.length - 1}
                                                            onClick={() => handleMove(index, 'down')}
                                                            className="inline-flex h-5 w-5 items-center justify-center rounded text-slate-500 hover:bg-slate-200 hover:text-slate-800 disabled:opacity-30 disabled:pointer-events-none"
                                                            aria-label={`Move ${item.label} down`}
                                                            title="Move down"
                                                        >
                                                            <svg className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clipRule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>

                                                {/* Icon + Label */}
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2.5">
                                                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 border border-blue-200/60">
                                                            <NavigationIcon name={item.icon} className="h-4 w-4" />
                                                        </span>
                                                        <span className="font-medium text-slate-900">{item.label}</span>
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
                                        ))}
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
                                <span className="text-[11px] text-slate-400">Preview</span>
                            </div>

                            {previewGroups.length === 0 ? (
                                <div className="py-6 text-center text-xs text-slate-400">
                                    No shortcuts are currently enabled. The menu in the header will be hidden.
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
                                <span><strong>Permissions Guard:</strong> Shortcuts are automatically filtered by your workspace permissions. If a user lacks permission for a linked module, it will be safely hidden.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600" />
                                <span><strong>Local Storage:</strong> Configurations are saved in your current browser session and persist across reloads.</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600" />
                                <span><strong>Fast Access:</strong> You can quickly reach this configuration page anytime by clicking &quot;Customize&quot; at the top of the Quick Access dropdown.</span>
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
                                hint="Optional: select a common AutoERP page to automatically fill in details."
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
