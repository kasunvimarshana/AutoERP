import { mockDelay } from './mockDelay';
import type { ApiPreviewResponse } from '../api/apiResponse';

export async function mockPreviewResponse<TInput, TCalculated>(
    input: TInput,
    calculated: TCalculated,
    breakdown: Array<Record<string, unknown>> = [],
    warnings: string[] = ['Mock preview only. Backend will calculate authoritative values.'],
): Promise<ApiPreviewResponse<TInput, TCalculated>> {
    await mockDelay();

    return {
        breakdown,
        calculated,
        errors: [],
        input,
        warnings,
    };
}
