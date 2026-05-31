export type AuthUser = {
    email: string;
    firstName?: string;
    id: string;
    lastName?: string;
    name: string;
    organizationUnitId?: string;
    permissions: string[];
    role: string;
    roles: string[];
    status: string;
    tenantId?: string;
};

export type AuthSession = {
    accessToken: string;
    accessTokenExpiresAt?: string;
    organizationUnitId?: string;
    refreshToken?: string;
    refreshTokenExpiresAt?: string;
    sessionId?: string;
    tenantId?: string;
    tokenType: string;
    user: AuthUser;
};

export type LoginInput = {
    loginIdentifier: string;
    organizationUnitId?: string;
    password: string;
    remember: boolean;
    tenantId?: string;
};

export type ForgotPasswordInput = {
    loginIdentifier: string;
};

export type ResetPasswordInput = {
    password: string;
    passwordConfirmation: string;
    token: string;
};

export type ChangePasswordInput = {
    currentPassword: string;
    newPassword: string;
    newPasswordConfirmation: string;
};
