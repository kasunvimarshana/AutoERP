import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { AuthSession, CurrentUserResponse, LoginPayload } from './authTypes';

export const authApi = {
    async login(payload: LoginPayload): Promise<AuthSession> {
        const { data } = await apiClient.post<AuthSession>(`${endpoints.auth}/login`, payload);
        return data;
    },

    async logout(payload: { access_token?: string | null; session_id?: number | null } = {}): Promise<void> {
        await apiClient.post(`${endpoints.auth}/logout`, payload);
    },

    async me(signal?: AbortSignal): Promise<CurrentUserResponse> {
        const { data } = await apiClient.get<CurrentUserResponse>(`${endpoints.auth}/me`, { signal });
        return data;
    },
};
