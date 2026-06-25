import { useMemo, useState } from 'react';
import { authApi } from '@/modules/auth/authApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';

export function OrganizationUnitSwitcher() {
    const auth = useAuth();
    const [switchError, setSwitchError] = useState<ApiError | null>(null);
    const [switching, setSwitching] = useState(false);
    const optionsRequest = useApi(
        (signal) => authApi.organizationUnitOptions(signal),
        [auth.user?.id],
        auth.authMode === 'tenant' && auth.isAuthenticated,
    );
    const options = useMemo(
        () => (optionsRequest.data?.data ?? []).map((organizationUnit) => ({
            value: String(organizationUnit.id),
            label: organizationUnit.code
                ? `${organizationUnit.name ?? 'Organization unit'} · ${organizationUnit.code}`
                : organizationUnit.name ?? 'Organization unit',
        })),
        [optionsRequest.data],
    );
    const currentId = auth.organizationUnit?.id === undefined || auth.organizationUnit?.id === null
        ? ''
        : String(auth.organizationUnit.id);

    if (auth.authMode !== 'tenant' || !auth.isAuthenticated) return null;

    return (
        <div className="hidden min-w-56 max-w-72 lg:block">
            <Select
                aria-label="Current organization unit"
                value={currentId}
                options={options}
                placeholder={optionsRequest.loading ? 'Loading organization units…' : 'Select organization unit'}
                disabled={switching || optionsRequest.loading || options.length === 0}
                error={(switchError ?? optionsRequest.error)?.message}
                onChange={async (event) => {
                    const organizationUnitId = Number(event.target.value);
                    if (!Number.isSafeInteger(organizationUnitId) || organizationUnitId < 1 || String(organizationUnitId) === currentId) {
                        return;
                    }

                    setSwitchError(null);
                    setSwitching(true);
                    try {
                        await auth.switchOrganizationUnit(organizationUnitId);
                    } catch (error: unknown) {
                        setSwitchError(toApiError(error));
                    } finally {
                        setSwitching(false);
                    }
                }}
            />
        </div>
    );
}
