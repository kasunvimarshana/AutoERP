import { useEffect, useMemo, useState } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { FieldError } from '../forms/FieldError';
import { FormSection } from '../forms/FormSection';
import { Button } from '../ui/Button';
import { Checkbox } from '../ui/Checkbox';
import { EmptyState } from '../ui/EmptyState';
import { Input } from '../ui/Input';
import { Select } from '../ui/Select';
import { Spinner } from '../ui/Spinner';

export type SettingsOption = {
    label: string;
    value: string;
};

export type SettingsField = {
    currentLabel?: string;
    description?: string;
    key: string;
    label: string;
    loadOptions?: () => Promise<SettingsOption[]>;
    options?: SettingsOption[];
    placeholder?: string;
    section: string;
    type: 'boolean' | 'number' | 'select' | 'text';
};

type SettingsEditorProps = {
    fields: SettingsField[];
    initialValues: Record<string, unknown>;
    onInitialize?: () => Promise<void>;
    onSave: (payload: Record<string, unknown>) => Promise<void>;
    title?: string;
};

type OptionState = {
    error?: string;
    loaded: boolean;
    loading: boolean;
    options: SettingsOption[];
};

function fieldValue(value: unknown): string {
    return value === null || value === undefined ? '' : String(value);
}

function isBlank(value: unknown) {
    return value === null || value === undefined || value === '';
}

function normalizeInitial(fields: SettingsField[], input: Record<string, unknown>) {
    return Object.fromEntries(fields.map((field) => [field.key, field.type === 'boolean' ? input[field.key] === true || input[field.key] === 1 || input[field.key] === '1' : fieldValue(input[field.key])]));
}

function toBackendValue(field: SettingsField, value: unknown) {
    if (field.type === 'boolean') {
        return value === true;
    }

    if (isBlank(value)) {
        return null;
    }

    if (field.type === 'number' || field.key.endsWith('_id')) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : value;
    }

    return value;
}

function sameValues(left: Record<string, unknown>, right: Record<string, unknown>) {
    const keys = new Set([...Object.keys(left), ...Object.keys(right)]);

    for (const key of keys) {
        if (left[key] !== right[key]) {
            return false;
        }
    }

    return true;
}

function errorMessage(error: unknown) {
    return error instanceof Error ? error.message : 'Unable to save settings.';
}

export function SettingsEditor({ fields, initialValues, onInitialize, onSave, title = 'Settings' }: SettingsEditorProps) {
    const baseline = useMemo(() => normalizeInitial(fields, initialValues), [fields, initialValues]);
    const [values, setValues] = useState<Record<string, unknown>>(baseline);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [formError, setFormError] = useState('');
    const [message, setMessage] = useState('');
    const [isSaving, setIsSaving] = useState(false);
    const [isInitializing, setIsInitializing] = useState(false);
    const [optionState, setOptionState] = useState<Record<string, OptionState>>({});

    useEffect(() => {
        setValues((current) => (sameValues(current, baseline) ? current : baseline));
        setFieldErrors({});
        setFormError('');
        setMessage('');
    }, [baseline]);

    const groupedFields = useMemo(() => {
        const groups = new Map<string, SettingsField[]>();
        fields.forEach((field) => {
            groups.set(field.section, [...(groups.get(field.section) ?? []), field]);
        });
        return Array.from(groups.entries());
    }, [fields]);

    const isDirty = !sameValues(values, baseline);

    const loadOptions = async (field: SettingsField) => {
        if (!field.loadOptions) {
            return;
        }

        const current = optionState[field.key];
        if (current?.loaded || current?.loading) {
            return;
        }

        setOptionState((state) => ({
            ...state,
            [field.key]: { loaded: false, loading: true, options: state[field.key]?.options ?? [] },
        }));

        try {
            const options = await field.loadOptions();
            setOptionState((state) => ({
                ...state,
                [field.key]: { loaded: true, loading: false, options },
            }));
        } catch (error) {
            setOptionState((state) => ({
                ...state,
                [field.key]: {
                    error: errorMessage(error),
                    loaded: false,
                    loading: false,
                    options: state[field.key]?.options ?? [],
                },
            }));
        }
    };

    const reset = () => {
        setValues(baseline);
        setFieldErrors({});
        setFormError('');
        setMessage('Changes reset.');
    };

    const save = async () => {
        setIsSaving(true);
        setFieldErrors({});
        setFormError('');
        setMessage('');

        try {
            const payload = Object.fromEntries(fields.map((field) => [field.key, toBackendValue(field, values[field.key])]));
            await onSave(payload);
            setMessage('Settings saved.');
        } catch (error) {
            if (error instanceof ApiError) {
                setFieldErrors(Object.fromEntries(Object.entries(error.errors).map(([key, messages]) => [key, messages[0] ?? 'Invalid value.'])));
                setFormError(error.message);
            } else {
                setFormError(errorMessage(error));
            }
        } finally {
            setIsSaving(false);
        }
    };

    const initialize = async () => {
        if (!onInitialize) {
            return;
        }

        setIsInitializing(true);
        setFormError('');
        setMessage('');

        try {
            await onInitialize();
            setMessage('Default settings initialized.');
        } catch (error) {
            setFormError(errorMessage(error));
        } finally {
            setIsInitializing(false);
        }
    };

    if (fields.length === 0) {
        return <EmptyState description="No editable settings were returned for this module." title="No settings available" />;
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 className="text-base font-semibold text-slate-950">{title}</h2>
                    <p className="mt-1 text-sm text-slate-500">Changes are validated and stored by the backend.</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    {onInitialize ? (
                        <Button disabled={isInitializing || isSaving} onClick={initialize} title="Create backend defaults for this module." variant="secondary">
                            {isInitializing ? <Spinner /> : null}
                            Initialize
                        </Button>
                    ) : null}
                    <Button disabled={!isDirty || isSaving} onClick={reset} title={isDirty ? 'Discard unsaved changes.' : 'No changes to reset.'} variant="secondary">
                        Reset
                    </Button>
                    <Button disabled={!isDirty || isSaving} onClick={save} title={isDirty ? 'Save settings.' : 'Change a setting before saving.'}>
                        {isSaving ? <Spinner /> : null}
                        Save
                    </Button>
                </div>
            </div>

            {formError ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}
            {message ? <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{message}</div> : null}

            {groupedFields.map(([section, sectionFields]) => (
                <FormSection key={`settings-section-${section}`} title={section}>
                    <div className="grid gap-4 md:grid-cols-2">
                        {sectionFields.map((field) => {
                            const state = optionState[field.key];
                            const staticOptions = field.options ?? [];
                            const loadedOptions = state?.options ?? [];
                            const currentValue = fieldValue(values[field.key]);
                            const currentOption = currentValue ? [{ label: field.currentLabel || 'Configured value', value: currentValue }] : [];
                            const options = [...currentOption, ...staticOptions, ...loadedOptions].filter(
                                (option, index, list) => list.findIndex((candidate) => candidate.value === option.value && candidate.label === option.label) === index,
                            );

                            return (
                                <label className="space-y-1.5" key={`settings-field-${field.section}-${field.key}`}>
                                    <span className="text-sm font-medium text-slate-700">{field.label}</span>
                                    {field.description ? <span className="block text-xs text-slate-500">{field.description}</span> : null}
                                    {field.type === 'boolean' ? (
                                        <div className="flex h-11 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50/60 px-3">
                                            <Checkbox
                                                checked={values[field.key] === true}
                                                onChange={(event) => setValues((current) => ({ ...current, [field.key]: event.target.checked }))}
                                            />
                                            <span className="text-sm text-slate-600">{values[field.key] === true ? 'Enabled' : 'Disabled'}</span>
                                        </div>
                                    ) : field.type === 'select' ? (
                                        <>
                                            <Select
                                                onChange={(event) => setValues((current) => ({ ...current, [field.key]: event.target.value }))}
                                                onFocus={() => loadOptions(field)}
                                                onMouseDown={() => loadOptions(field)}
                                                options={options}
                                                placeholder={state?.loading ? 'Loading options...' : field.placeholder ?? 'Select a value'}
                                                value={currentValue}
                                            />
                                            {state?.error ? <p className="text-xs text-red-600">{state.error}</p> : null}
                                        </>
                                    ) : (
                                        <Input
                                            onChange={(event) => setValues((current) => ({ ...current, [field.key]: event.target.value }))}
                                            placeholder={field.placeholder}
                                            type={field.type === 'number' ? 'number' : 'text'}
                                            value={currentValue}
                                        />
                                    )}
                                    <FieldError message={fieldErrors[field.key]} />
                                </label>
                            );
                        })}
                    </div>
                </FormSection>
            ))}
        </div>
    );
}
