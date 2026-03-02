/**
 * Shared API response envelope — mirrors Laravel ApiResponse shape.
 *
 * Every API endpoint wraps its payload in this structure:
 *   { success, message, data, meta?, errors? }
 */
export interface ApiResponse<T = unknown> {
  success: boolean
  message: string
  data: T
  meta?: PaginationMeta
  errors?: Record<string, string[]>
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: PaginationMeta
}

/** Standard list query parameters used across all paginated endpoints. */
export interface ListParams {
  page?: number
  per_page?: number
  search?: string
  sort?: string
  direction?: 'asc' | 'desc'
  [key: string]: unknown
}
