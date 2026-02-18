// IAM (Identity and Access Management) Module Types

export interface User {
  id: number
  name: string
  email: string
  phone?: string
  avatar?: string
  is_active: boolean
  email_verified_at?: string
  last_login_at?: string
  last_login_ip?: string
  login_count?: number
  roles: Role[]
  permissions: string[]
  tenant_id: number
  department?: string
  job_title?: string
  employee_id?: string
  created_at: string
  updated_at: string
  created_by?: number
  updated_by?: number
}

export interface Role {
  id: number
  name: string
  display_name?: string
  description?: string
  guard_name: string
  level?: number
  parent_id?: number
  parent?: Role
  children?: Role[]
  permissions: Permission[]
  users_count?: number
  is_system?: boolean
  created_at: string
  updated_at: string
}

export interface Permission {
  id: number
  name: string
  display_name?: string
  description?: string
  guard_name: string
  resource?: string
  action?: string
  module?: string
  is_system?: boolean
  created_at: string
  updated_at: string
}

export interface LoginAttempt {
  id: number
  user_id?: number
  email: string
  ip_address: string
  user_agent: string
  successful: boolean
  failure_reason?: string
  attempted_at: string
}

export interface Session {
  id: string
  user_id: number
  ip_address: string
  user_agent: string
  last_activity: string
  is_current?: boolean
}

export interface MFASetup {
  secret: string
  qr_code: string
  recovery_codes: string[]
}

export interface AuditLog {
  id: number
  user_id?: number
  user_name?: string
  event: string
  auditable_type: string
  auditable_id: number
  old_values?: Record<string, any>
  new_values?: Record<string, any>
  url?: string
  ip_address?: string
  user_agent?: string
  tags?: string[]
  created_at: string
}

// Query Parameters
export interface UserQueryParams {
  page?: number
  per_page?: number
  search?: string
  is_active?: boolean
  role?: string
  role_id?: number
  department?: string
  order_by?: string
  order_direction?: 'asc' | 'desc'
}

export interface RoleQueryParams {
  page?: number
  per_page?: number
  search?: string
  parent_id?: number
  level?: number
}

export interface PermissionQueryParams {
  page?: number
  per_page?: number
  search?: string
  resource?: string
  module?: string
  action?: string
}

export interface LoginAttemptQueryParams {
  page?: number
  per_page?: number
  user_id?: number
  successful?: boolean
  date_from?: string
  date_to?: string
}

export interface AuditLogQueryParams {
  page?: number
  per_page?: number
  user_id?: number
  event?: string
  auditable_type?: string
  auditable_id?: number
  date_from?: string
  date_to?: string
}

// Form Data
export interface UserFormData {
  name: string
  email: string
  phone?: string
  password?: string
  password_confirmation?: string
  is_active?: boolean
  role_ids?: number[]
  department?: string
  job_title?: string
  employee_id?: string
}

export interface UserProfileFormData {
  name: string
  phone?: string
  avatar?: File | string
  department?: string
  job_title?: string
}

export interface RoleFormData {
  name: string
  display_name?: string
  description?: string
  parent_id?: number
  permission_ids?: number[]
}

export interface PermissionFormData {
  name: string
  display_name?: string
  description?: string
  resource?: string
  action?: string
  module?: string
}

export interface ChangePasswordData {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

export interface ResetPasswordData {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export interface MFAVerificationData {
  code: string
}

export interface RoleAssignmentData {
  user_id: number
  role_ids: number[]
}

export interface PermissionAssignmentData {
  role_id: number
  permission_ids: number[]
}

// Additional Types
export interface PermissionGroup {
  module: string
  permissions: Permission[]
}

export interface RoleHierarchy {
  role: Role
  children: RoleHierarchy[]
}

export interface UserActivity {
  id: number
  user_id: number
  action: string
  description: string
  ip_address: string
  user_agent: string
  created_at: string
}

export interface SecuritySettings {
  mfa_enabled: boolean
  session_timeout: number
  password_expiry_days: number
  max_login_attempts: number
  lockout_duration: number
}

export interface PasswordPolicy {
  min_length: number
  require_uppercase: boolean
  require_lowercase: boolean
  require_numbers: boolean
  require_special_chars: boolean
  prevent_reuse_count: number
}
