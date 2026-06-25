import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { AcceptInitialAdministratorInvitationPayload, AcceptPlatformOperatorInvitationPayload, AuthSession, CurrentUserResponse, InitialAdministratorInvitationAcceptance, InitialAdministratorInvitationInspection, LoginPayload, PlatformMfaConfirmation, PlatformMfaEnrollment, PlatformOperatorInvitationAcceptance, PlatformOperatorInvitationInspection } from './authTypes';

const platformAuthEndpoint = '/api/v1/platform/auth';

export const authApi = {

    async inspectInitialAdministratorInvitation(token: string, signal?: AbortSignal): Promise<InitialAdministratorInvitationInspection> {
        const { data } = await apiClient.post<{ data: InitialAdministratorInvitationInspection }>(
            '/api/v1/auth/initial-administrator/inspect',
            { token },
            { signal },
        );
        return data.data;
    },

    async acceptInitialAdministratorInvitation(payload: AcceptInitialAdministratorInvitationPayload): Promise<InitialAdministratorInvitationAcceptance> {
        const { data } = await apiClient.post<{ data: InitialAdministratorInvitationAcceptance }>(
            '/api/v1/auth/initial-administrator/accept',
            payload,
        );
        return data.data;
    },

    async inspectPlatformOperatorInvitation(token: string, signal?: AbortSignal): Promise<PlatformOperatorInvitationInspection> {
        const { data } = await apiClient.post<{ data: PlatformOperatorInvitationInspection }>(
            '/api/v1/platform/operator-invitations/inspect',
            { token },
            { signal },
        );
        return data.data;
    },

    async acceptPlatformOperatorInvitation(payload: AcceptPlatformOperatorInvitationPayload): Promise<PlatformOperatorInvitationAcceptance> {
        const { data } = await apiClient.post<{ data: PlatformOperatorInvitationAcceptance }>(
            '/api/v1/platform/operator-invitations/accept',
            payload,
        );
        return data.data;
    },

    async login(payload: LoginPayload): Promise<AuthSession> {
        if (payload.auth_mode === 'platform') {
            const { data } = await apiClient.post<AuthSession>(`${platformAuthEndpoint}/login`, {
                email: payload.login_identifier,
                password: payload.password,
                totp_code: payload.totp_code || undefined,
                backup_code: payload.backup_code || undefined,
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


    async startPlatformMfaEnrollment(email: string, password: string): Promise<PlatformMfaEnrollment> {
        const { data } = await apiClient.post<PlatformMfaEnrollment>(`${platformAuthEndpoint}/mfa/enrollment`, { email, password });
        return data;
    },

    async confirmPlatformMfaEnrollment(email: string, password: string, code: string): Promise<PlatformMfaConfirmation> {
        const { data } = await apiClient.post<PlatformMfaConfirmation>(`${platformAuthEndpoint}/mfa/enrollment/confirm`, { email, password, code });
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
