import { ChevronUp, ChevronDown } from 'lucide-react'

function SkeletonRow({ colCount }) {
  return (
    <tr className="animate-pulse">
      {Array.from({ length: colCount }).map((_, i) => (
        <td key={i} className="px-4 py-3">
          <div className="h-4 bg-gray-200 rounded w-3/4" />
        </td>
      ))}
    </tr>
  )
}

export default function Table({
  columns = [],
  data = [],
  loading = false,
  onSort,
  sortKey,
  sortDir,
  emptyMessage = 'No data available.',
}) {
  function handleSort(col) {
    if (!col.sortable || !onSort) return
    const newDir = sortKey === col.key && sortDir === 'asc' ? 'desc' : 'asc'
    onSort(col.key, newDir)
  }

  return (
    <div className="w-full overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
      <table className="min-w-full divide-y divide-gray-200 text-sm">
        <thead className="bg-gray-50">
          <tr>
            {columns.map((col) => (
              <th
                key={col.key}
                scope="col"
                onClick={() => handleSort(col)}
                className={[
                  'px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider select-none',
                  col.sortable && onSort ? 'cursor-pointer hover:bg-gray-100' : '',
                ].join(' ')}
              >
                <span className="inline-flex items-center gap-1">
                  {col.label}
                  {col.sortable && onSort && (
                    <span className="flex flex-col">
                      <ChevronUp
                        className={`w-3 h-3 -mb-0.5 ${
                          sortKey === col.key && sortDir === 'asc'
                            ? 'text-blue-600'
                            : 'text-gray-400'
                        }`}
                      />
                      <ChevronDown
                        className={`w-3 h-3 -mt-0.5 ${
                          sortKey === col.key && sortDir === 'desc'
                            ? 'text-blue-600'
                            : 'text-gray-400'
                        }`}
                      />
                    </span>
                  )}
                </span>
              </th>
            ))}
          </tr>
        </thead>

        <tbody className="bg-white divide-y divide-gray-100">
          {loading ? (
            Array.from({ length: 5 }).map((_, i) => (
              <SkeletonRow key={i} colCount={columns.length} />
            ))
          ) : data.length === 0 ? (
            <tr>
              <td
                colSpan={columns.length}
                className="px-4 py-10 text-center text-gray-400 text-sm"
              >
                {emptyMessage}
              </td>
            </tr>
          ) : (
            data.map((row, rowIdx) => (
              <tr
                key={row.id ?? rowIdx}
                className="hover:bg-gray-50 transition-colors duration-100"
              >
                {columns.map((col) => (
                  <td key={col.key} className="px-4 py-3 text-gray-700 whitespace-nowrap">
                    {col.render ? col.render(row[col.key], row) : row[col.key] ?? '—'}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  )
}
