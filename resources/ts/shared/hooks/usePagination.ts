import { useState } from 'react';
import type { PaginationState } from '../types/pagination.types';

export function usePagination(initial: PaginationState = { page: 1, perPage: 10 }) {
    return useState<PaginationState>(initial);
}
