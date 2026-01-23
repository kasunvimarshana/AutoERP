/**
 * Authentication and Authorization Types
 */

export interface User {
  id: number
  uuid: string
  tenant_id: number | null
  name: string
  email: string
  email_verified_at: string | null
  phone: string | null
  status: 'active' | 'inactive' | 'suspended'
  last_login_at: string | null
  avatar_url: string | null
  preferences: Record<string, any>
  metadata: Record<string, any>
  created_at: string
  updated_at: string
  roles?: Role[]
  permissions?: string[]
  tenant?: Tenant
}

export interface Role {
  id: number
  name: string
  display_name: string
  description: string | null
  permissions?: Permission[]
}

export interface Permission {
  id: number
  name: string
  display_name: string
  description: string | null
  module: string
}

export interface Tenant {
  id: number
  uuid: string
  name: string
  slug: string
  domain: string | null
  status: 'active' | 'inactive' | 'suspended'
  subscription_status: 'trial' | 'active' | 'expired' | 'cancelled'
  subscription_end_date: string | null
  settings: Record<string, any>
  metadata: Record<string, any>
  created_at: string
  updated_at: string
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
  phone?: string
  tenant_name?: string
  tenant_slug?: string
}

export interface AuthResponse {
  success: boolean
  message: string
  data: {
    user: User
    token: string
    expires_at: string
  }
}

export interface PasswordResetRequest {
  email: string
}

export interface PasswordResetConfirm {
  email: string
  token: string
  password: string
  password_confirmation: string
}

export interface PasswordChangeData {
  current_password: string
  new_password: string
  new_password_confirmation: string
}
