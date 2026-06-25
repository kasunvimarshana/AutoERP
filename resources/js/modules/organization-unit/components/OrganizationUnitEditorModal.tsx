import { useCallback, useMemo, useState, type FormEvent } from 'react';
import { ApiError, toApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import {
    organizationUnitApi,
    type OrganizationUnitCreatePayload,
    type OrganizationUnitSummary,
    type OrganizationUnitUpdatePayload,
    type OrganizationUnitType,
} from '../organizationUnitApi';

export function OrganizationUnitEditorModal({
    open,
    unit,
    types,
    onClose,
    onSaved,
}: {
    open: boolean;
    unit: OrganizationUnitSummary | null;
    types: OrganizationUnitType[];
    onClose: () => void;
    onSaved: (unit: OrganizationUnitSummary) => void;
}) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const [name, setName] = useState(unit?.name ?? '');
    const [code, setCode] = useState(unit?.code ?? '');
    const [description, setDescription] = useState(unit?.description ?? '');
    const [parent, setParent] = useState<OrganizationUnitSummary | null>(() => parentSummary(unit));
    const [typeId, setTypeId] = useState(unit?.type_id ? String(unit.type_id) : '');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const isRoot = unit?.is_root === true;
    const targetDepth = isRoot ? 0 : parent ? parent.depth + 1 : null;
    const availableTypes = useMemo(
        () => types.filter((type) => type.is_active && (targetDepth === null || type.level === targetDepth)),
        [targetDepth, types],
    );
    const dirty = name !== (unit?.name ?? '')
        || code !== (unit?.code ?? '')
        || description !== (unit?.description ?? '')
        || parent?.id !== (unit?.parent_id ?? null)
        || typeId !== (unit?.type_id ? String(unit.type_id) : '');

    const searchParents = useCallback(async ({ search, page, perPage, signal }: {
        search: string;
        page: number;
        perPage: number;
        signal: AbortSignal;
    }) => organizationUnitApi.searchParentCandidates({
        search,
        page,
        perPage,
        targetUnitId: unit?.id,
    }, signal), [unit?.id]);

    const requestClose = async () => {
        if (submitting) return;
        if (dirty && !await confirm({
            title: 'Discard organization-unit changes?',
            message: 'The unsaved hierarchy and profile changes will be lost.',
            confirmLabel: 'Discard changes',
        })) return;
        onClose();
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        const normalizedName = name.trim();
        const normalizedCode = code.trim();
        const selectedTypeId = Number(typeId);

        if (!normalizedName || !normalizedCode || !Number.isSafeInteger(selectedTypeId) || selectedTypeId < 1) {
            setError(new ApiError('Name, code, and a matching organization-unit type are required.', 422, 'INVALID_ORGANIZATION_UNIT', 'validation'));
            return;
        }
        if (!isRoot && !parent) {
            setError(new ApiError('Select the parent organization unit.', 422, 'PARENT_REQUIRED', 'validation'));
            return;
        }

        const commonPayload = {
            name: normalizedName,
            description: description.trim() || null,
            type_id: selectedTypeId,
            ...(isRoot ? {} : { parent_id: parent?.id }),
        };

        setSubmitting(true);
        setError(null);
        try {
            const saved = unit
                ? await organizationUnitApi.update(unit.id, {
                    ...commonPayload,
                    expected_version: unit.row_version,
                } satisfies OrganizationUnitUpdatePayload)
                : await organizationUnitApi.create({
                    ...commonPayload,
                    code: normalizedCode,
                } satisfies OrganizationUnitCreatePayload);
            onSaved(saved);
        } catch (caught: unknown) {
            setError(toApiError(caught));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <>
            <Modal
                open={open}
                title={unit ? `Edit ${unit.name}` : 'Create organization unit'}
                onClose={() => void requestClose()}
                closeDisabled={submitting}
            >
                <form className="space-y-5" onSubmit={(event) => void submit(event)}>
                    <ErrorAlert error={error} />
                    <div className="rounded-lg border border-sky-100 bg-sky-50 p-3 text-sm text-sky-900">
                        The parent determines the hierarchy depth. Only active types defined for that depth can be selected.
                    </div>
                    {!isRoot && (
                        <GenericLookupSelect
                            label="Parent organization unit"
                            value={parent}
                            search={searchParents}
                            formatLabel={(candidate) => `${candidate.name} · ${candidate.path}`}
                            onChange={(candidate) => {
                                setParent(candidate);
                                setTypeId('');
                            }}
                            minSearchLength={0}
                            loadOnOpen
                            placeholder="Search organization units"
                            required
                        />
                    )}
                    <Select
                        label="Organization-unit type"
                        value={typeId}
                        options={availableTypes.map((type) => ({
                            value: String(type.id),
                            label: `${type.name} · level ${type.level}`,
                        }))}
                        hint={targetDepth === null ? 'Select a parent first.' : `Only level ${targetDepth} types are valid here.`}
                        disabled={targetDepth === null}
                        onChange={(event) => setTypeId(event.target.value)}
                        required
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="Name" value={name} maxLength={255} onChange={(event) => setName(event.target.value)} required />
                        <Input
                            label="Code"
                            value={code}
                            maxLength={100}
                            pattern="[A-Za-z0-9][A-Za-z0-9_-]*"
                            hint="Letters, numbers, hyphens, and underscores only. Codes are immutable identifiers within the tenant."
                            onChange={(event) => setCode(event.target.value)}
                            disabled={unit !== null}
                            required
                        />
                    </div>
                    <Textarea
                        label="Description"
                        value={description}
                        maxLength={65535}
                        onChange={(event) => setDescription(event.target.value)}
                    />
                    <FormActions>
                        <Button type="button" variant="secondary" disabled={submitting} onClick={() => void requestClose()}>Cancel</Button>
                        <Button type="submit" loading={submitting}>{unit ? 'Save changes' : 'Create organization unit'}</Button>
                    </FormActions>
                </form>
            </Modal>
            {confirmDialog}
        </>
    );
}

function parentSummary(unit: OrganizationUnitSummary | null): OrganizationUnitSummary | null {
    const parent = unit?.parent;
    if (!parent) return null;
    return {
        id: parent.id,
        name: parent.name,
        code: parent.code,
        path: parent.path,
        depth: parent.depth,
        is_active: parent.is_active,
        retired_at: parent.retired_at ?? null,
        description: null,
        type_id: 0,
        parent_id: null,
        is_root: parent.depth === 0,
        row_version: 0,
        lifecycle_status: parent.retired_at ? 'retired' : parent.is_active ? 'active' : 'inactive',
    };
}
