import type { AuthToken, AuthUser, LoginPayload, RegisterPayload } from '../types/auth';
import { apiClient } from './client';

export const authApi = {
    login(payload: LoginPayload) {
        return apiClient.post<AuthToken>('/auth/login', payload, { includeTenantHeader: false });
    },
    register(payload: RegisterPayload) {
        return apiClient.post<AuthToken>('/auth/register', payload, { includeTenantHeader: false });
    },
    me() {
        return apiClient.get<AuthUser>('/auth/me');
    },
    logout() {
        return apiClient.post<{ message: string }>('/auth/logout');
    },
};
