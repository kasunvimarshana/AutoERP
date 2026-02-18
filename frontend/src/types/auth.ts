export interface User {
  id: number
  name: string
  email: string
  tenant_id: string
  roles?: Role[]
  permissions?: string[]
}

export interface Role {
  id: number
  name: string
  permissions?: Permission[]
}

export interface Permission {
  id: number
  name: string
}

export interface LoginCredentials {
  email: string
  password: string
  tenant_id?: string
}

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
  tenant_id?: string
}

export interface ApiResponse<T = any> {
  success: boolean
  message?: string
  data?: T
  errors?: Record<string, string[]>
}

export interface PaginatedResponse<T = any> {
  data: T[]
  current_page: number
  per_page: number
  total: number
  last_page: number
}
