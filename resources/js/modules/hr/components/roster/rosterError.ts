/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { ApiError } from '@/shared/api/apiError';
import type { ApiErrorPayload } from '@/shared/types/api';

interface AxiosLikeError {
    response?: {
        data?: ApiErrorPayload;
    };
    message?: string;
}

export function extractHrApiError(err: unknown, fallback: string): string {
    if (err instanceof ApiError) {
        const firstKey = Object.keys(err.fields)[0];
        if (firstKey && err.fields[firstKey]?.length) {
            return err.fields[firstKey][0];
        }
        return err.message || fallback;
    }
    if (err && typeof err === 'object') {
        const axiosErr = err as AxiosLikeError;
        const data = axiosErr.response?.data;
        if (data?.errors) {
            const firstKey = Object.keys(data.errors)[0];
            if (firstKey && data.errors[firstKey]?.length) {
                return data.errors[firstKey][0];
            }
        }
        if (data?.error?.details?.fields) {
            const fields = data.error.details.fields;
            const firstKey = Object.keys(fields)[0];
            if (firstKey && fields[firstKey]?.length) {
                return fields[firstKey][0];
            }
        }
        if (data?.message) return data.message;
        if (data?.error?.message) return data.error.message;
        if (axiosErr.message) return axiosErr.message;
    }
    return fallback;
}
