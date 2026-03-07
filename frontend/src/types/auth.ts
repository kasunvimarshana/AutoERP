export interface User {
  id: number;
  name: string;
  email: string;
  tenant_id: number | null;
  roles: Role[];
  permissions: string[];
  created_at: string;
  updated_at: string;
}

export interface Role {
  id: number;
  name: string;
  guard_name: string;
  permissions: Permission[];
}

export interface Permission {
  id: number;
  name: string;
  guard_name: string;
}

export interface AuthTokens {
  access_token: string;
  token_type: string;
  expires_in: number;
}

export interface LoginCredentials {
  email: string;
  password: string;
  tenant_id?: number;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  tenant_id?: number;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
}
