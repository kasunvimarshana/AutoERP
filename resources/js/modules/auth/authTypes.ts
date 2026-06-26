import type { AuthMode } from '@/shared/api/authSessionStorage';

export interface AuthUser {
    id: number | string;
    name: string | null;
    email: string | null;
    roles?: string[];
    permissions?: string[];
    is_platform_operator?: boolean;
}

export interface AuthTenant {
    id: number | string;
    name: string | null;
    timezone?: string | null;
}

export interface AuthOrganizationUnit {
    id: number | string;
    name: string | null;
    code?: string | null;
    path?: string | null;
    timezone?: string | null;
}

export interface OrganizationUnitContextOptions {
    data: AuthOrganizationUnit[];
    default_organization_unit_ids: number[];
    current_organization_unit_id: number | null;
}

export interface AuthSession {
    token: string;
    token_type: 'Bearer' | string;
    user: AuthUser;
    tenant: AuthTenant | null;
    organization_unit: AuthOrganizationUnit | null;
    roles?: string[];
    permissions?: string[];
    enabled_modules?: string[] | null;
    is_platform_operator?: boolean;
}

export interface CurrentUserResponse {
    user: AuthUser;
    tenant: AuthTenant | null;
    organization_unit: AuthOrganizationUnit | null;
    roles?: string[];
    permissions?: string[];
    enabled_modules?: string[] | null;
    is_platform_operator?: boolean;
}

export interface LoginPayload {
    auth_mode: AuthMode;
    identifier: string;
    password: string;
    organization_unit_id?: number | null;
    device_name?: string | null;
    totp_code?: string | null;
    backup_code?: string | null;
}

export interface PlatformMfaEnrollment {
    enrollment_proof: string;
    provisioning_uri: string;
}

export interface PlatformMfaConfirmation {
    enabled: true;
    backup_codes: string[];
}


export interface InitialAdministratorInvitationInspection {
    tenant_name: string;
    email: string;
    expires_at: string;
}

export interface AcceptInitialAdministratorInvitationPayload {
    token: string;
    first_name: string;
    last_name: string | null;
    password: string;
    password_confirmation: string;
}

export interface InitialAdministratorInvitationAcceptance {
    user_id: number;
    tenant_id: number;
    email: string;
}

export interface PlatformOperatorInvitationInspection {
    operator_name: string;
    email: string;
    expires_at: string;
    delivery_status: string;
}

export interface AcceptPlatformOperatorInvitationPayload {
    token: string;
    password: string;
    password_confirmation: string;
}

export interface PlatformOperatorInvitationAcceptance {
    operator_name: string;
    email: string;
    status: 'active';
    mfa_enrollment?: PlatformMfaEnrollment;
}
