import { ChevronLeft, ChevronRight } from 'lucide-react'

function PageButton({ page, active, disabled, onClick, children }) {
  return (
    <button
      onClick={() => !disabled && onClick(page)}
      disabled={disabled}
      aria-current={active ? 'page' : undefined}
      className={[
        'relative inline-flex items-center justify-center min-w-[2rem] h-8 px-2 text-sm font-medium rounded',
        'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1',
        'transition-colors duration-100',
        disabled ? 'cursor-not-allowed text-gray-300' : '',
        active
          ? 'bg-blue-600 text-white border border-blue-600'
          : !disabled
          ? 'text-gray-700 border border-gray-300 hover:bg-gray-100'
          : 'text-gray-300 border border-gray-200',
      ].join(' ')}
    >
      {children ?? page}
    </button>
  )
}

function buildPageRange(current, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const pages = []
  pages.push(1)

  if (current > 3) pages.push('...')

  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)
  for (let i = start; i <= end; i++) pages.push(i)

  if (current < total - 2) pages.push('...')

  pages.push(total)
  return pages
}

export default function Pagination({
  currentPage,
  totalPages,
  onPageChange,
  totalItems = 0,
  perPage = 10,
}) {
  const firstItem = totalItems === 0 ? 0 : (currentPage - 1) * perPage + 1
  const lastItem = Math.min(currentPage * perPage, totalItems)
  const pages = buildPageRange(currentPage, totalPages)

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 py-3 px-1">
      <p className="text-sm text-gray-600 shrink-0">
        {totalItems === 0 ? (
          'No results'
        ) : (
          <>
            Showing <span className="font-medium">{firstItem}</span>–
            <span className="font-medium">{lastItem}</span> of{' '}
            <span className="font-medium">{totalItems}</span> results
          </>
        )}
      </p>

      <nav className="flex items-center gap-1" aria-label="Pagination">
        <PageButton
          page={currentPage - 1}
          disabled={currentPage === 1}
          onClick={onPageChange}
        >
          <ChevronLeft className="w-4 h-4" />
        </PageButton>

        {pages.map((p, i) =>
          p === '...' ? (
            <span key={`ellipsis-${i}`} className="px-1 text-gray-400 select-none">
              &hellip;
            </span>
          ) : (
            <PageButton
              key={p}
              page={p}
              active={p === currentPage}
              onClick={onPageChange}
            >
              {p}
            </PageButton>
          )
        )}

        <PageButton
          page={currentPage + 1}
          disabled={currentPage === totalPages || totalPages === 0}
          onClick={onPageChange}
        >
          <ChevronRight className="w-4 h-4" />
        </PageButton>
      </nav>
    </div>
  )
}
