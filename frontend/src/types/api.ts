/**
 * API Response and Request Types
 */

export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data: T
  errors?: Record<string, string[]>
  meta?: ApiMeta
}

export interface ApiMeta {
  current_page?: number
  from?: number
  last_page?: number
  per_page?: number
  to?: number
  total?: number
  path?: string
  links?: PaginationLink[]
}

export interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

export interface PaginatedResponse<T = any> {
  data: T[]
  meta: ApiMeta
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  status?: number
  code?: string
}

export interface ApiValidationError extends ApiError {
  errors: Record<string, string[]>
}

export interface QueryParams {
  page?: number
  per_page?: number
  search?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  filters?: Record<string, any>
  include?: string[]
  [key: string]: any
}
