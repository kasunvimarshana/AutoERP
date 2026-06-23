import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { AuthSession, CurrentUserResponse, LoginPayload } from './authTypes';

const platformAuthEndpoint = '/api/v1/platform/auth';

export const authApi = {
    async login(payload: LoginPayload): Promise<AuthSession> {
        if (payload.auth_mode === 'platform') {
            const { data } = await apiClient.post<AuthSession>(`${platformAuthEndpoint}/login`, {
                email: payload.login_identifier,
                password: payload.password,
            });
            return data;
        }

        const tenantPayload = {
            login_identifier: payload.login_identifier,
            password: payload.password,
            tenant_code: payload.tenant_code,
            organization_unit_id: payload.organization_unit_id,
            device_name: payload.device_name,
        };
        const { data } = await apiClient.post<AuthSession>(`${endpoints.auth}/login`, tenantPayload);
        return data;
    },

    async logout(authMode: LoginPayload['auth_mode'], payload: { access_token?: string | null; session_id?: number | null } = {}): Promise<void> {
        const endpoint = authMode === 'platform' ? platformAuthEndpoint : endpoints.auth;
        await apiClient.post(`${endpoint}/logout`, payload);
    },

    async me(authMode: LoginPayload['auth_mode'], signal?: AbortSignal): Promise<CurrentUserResponse> {
        const endpoint = authMode === 'platform' ? platformAuthEndpoint : endpoints.auth;
        const { data } = await apiClient.get<CurrentUserResponse>(`${endpoint}/me`, { signal });
        return data;
    },
};
