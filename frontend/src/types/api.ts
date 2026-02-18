/**
 * API Response Types for AutoERP Frontend
 * 
 * Standardized type definitions for all API interactions
 */

/**
 * Standard API error response from backend
 */
export interface ApiErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
  code?: string;
  statusCode?: number;
}

/**
 * Standard API success response from backend
 */
export interface ApiSuccessResponse<T = any> {
  success: true;
  message?: string;
  data: T;
}

/**
 * Paginated response metadata
 */
export interface PaginationInfo {
  currentPage: number;
  totalPages: number;
  perPage: number;
  totalRecords: number;
  hasNextPage: boolean;
  hasPreviousPage: boolean;
}

/**
 * Paginated API response
 */
export interface PaginatedApiResponse<T = any> {
  success: true;
  data: T[];
  pagination: PaginationInfo;
}

/**
 * Validation error details
 */
export interface ValidationErrors {
  [field: string]: string[];
}

/**
 * Network error details for client-side handling
 */
export interface NetworkErrorDetails {
  isTimeout: boolean;
  isNetworkError: boolean;
  isServerError: boolean;
  statusCode?: number;
  originalError?: any;
}

/**
 * Axios error with API error response
 */
export interface ApiError extends Error {
  response?: {
    data?: ApiErrorResponse;
    status?: number;
    statusText?: string;
  };
  request?: any;
  config?: any;
}

/**
 * Type guard to check if error is an ApiError
 */
export function isApiError(error: unknown): error is ApiError {
  return (
    typeof error === 'object' &&
    error !== null &&
    'response' in error
  );
}

/**
 * Extract error message from various error types
 */
export function getErrorMessage(error: unknown): string {
  if (isApiError(error)) {
    return error.response?.data?.message || error.message || 'An error occurred';
  }
  if (error instanceof Error) {
    return error.message;
  }
  return 'An unknown error occurred';
}

/**
 * Extract validation errors from API error
 */
export function getValidationErrors(error: unknown): Record<string, string[]> | null {
  if (isApiError(error) && error.response?.data?.errors) {
    return error.response.data.errors;
  }
  return null;
}
