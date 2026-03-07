import { useState, useCallback } from 'react';

interface PaginationState {
  page: number;
  perPage: number;
}

interface PaginationActions {
  setPage: (page: number) => void;
  setPerPage: (perPage: number) => void;
  nextPage: () => void;
  prevPage: () => void;
  resetPage: () => void;
}

export const usePagination = (initialPerPage = 15): PaginationState & PaginationActions => {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(initialPerPage);

  const nextPage = useCallback(() => setPage((p) => p + 1), []);
  const prevPage = useCallback(() => setPage((p) => Math.max(1, p - 1)), []);
  const resetPage = useCallback(() => setPage(1), []);

  const handleSetPerPage = useCallback((newPerPage: number) => {
    setPerPage(newPerPage);
    setPage(1);
  }, []);

  return { page, perPage, setPage, setPerPage: handleSetPerPage, nextPage, prevPage, resetPage };
};
