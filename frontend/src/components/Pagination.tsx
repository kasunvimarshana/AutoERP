import React from 'react';

interface PaginationProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
}

const Pagination: React.FC<PaginationProps> = ({ currentPage, lastPage, onPageChange }) => {
  if (lastPage <= 1) return null;

  return (
    <div style={{ display: 'flex', gap: '0.25rem', marginTop: '1rem', justifyContent: 'flex-end' }}>
      <button
        onClick={() => onPageChange(currentPage - 1)}
        disabled={currentPage === 1}
        style={{ padding: '0.375rem 0.75rem', border: '1px solid #d1d5db', borderRadius: '0.25rem', cursor: 'pointer', background: currentPage === 1 ? '#f3f4f6' : 'white' }}
      >
        ‹
      </button>
      {Array.from({ length: lastPage }, (_, i) => i + 1).map((page) => (
        <button
          key={page}
          onClick={() => onPageChange(page)}
          style={{
            padding: '0.375rem 0.75rem',
            border: '1px solid #d1d5db',
            borderRadius: '0.25rem',
            cursor: 'pointer',
            background: page === currentPage ? '#3b82f6' : 'white',
            color: page === currentPage ? 'white' : '#374151',
          }}
        >
          {page}
        </button>
      ))}
      <button
        onClick={() => onPageChange(currentPage + 1)}
        disabled={currentPage === lastPage}
        style={{ padding: '0.375rem 0.75rem', border: '1px solid #d1d5db', borderRadius: '0.25rem', cursor: 'pointer', background: currentPage === lastPage ? '#f3f4f6' : 'white' }}
      >
        ›
      </button>
    </div>
  );
};

export default Pagination;
