import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { AuthSession, CurrentUserResponse, LoginPayload, OrganizationUnitContextOptions, AuthOrganizationUnit } from './authTypes';

const platformAuthEndpoint = '/api/v1/platform/auth';

export const authApi = {
    async login(payload: LoginPayload): Promise<AuthSession> {
        if (payload.auth_mode === 'platform') {
            const { data } = await apiClient.post<AuthSession>(`${platformAuthEndpoint}/login`, {
                email: payload.identifier,
                password: payload.password,
                device_name: payload.device_name || undefined,
            });
            return data;
        }

        const tenantPayload = {
            identifier: payload.identifier,
            password: payload.password,
            organization_unit_id: payload.organization_unit_id,
            device_name: payload.device_name,
        };
        const { data } = await apiClient.post<AuthSession>(`${endpoints.auth}/login`, tenantPayload);
        return data;
    },


    async logout(authMode: LoginPayload['auth_mode']): Promise<void> {
        const endpoint = authMode === 'platform' ? platformAuthEndpoint : endpoints.auth;
        await apiClient.post(`${endpoint}/logout`);
    },

    async organizationUnitOptions(signal?: AbortSignal): Promise<OrganizationUnitContextOptions> {
        const { data } = await apiClient.get<OrganizationUnitContextOptions>(
            '/api/v1/organization-units/context/options',
            { signal },
        );
        return data;
    },

    async switchOrganizationUnit(organizationUnitId: number): Promise<AuthOrganizationUnit> {
        const { data } = await apiClient.post<{ data: AuthOrganizationUnit }>(
            '/api/v1/auth/organization-unit/switch',
            { target_organization_unit_id: organizationUnitId },
        );
        return data.data;
    },

    async me(authMode: LoginPayload['auth_mode'], signal?: AbortSignal): Promise<CurrentUserResponse> {
        const endpoint = authMode === 'platform' ? platformAuthEndpoint : endpoints.auth;
        const { data } = await apiClient.get<CurrentUserResponse>(`${endpoint}/me`, { signal });
        return data;
    },
};
