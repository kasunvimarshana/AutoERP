import React from 'react';

interface Column<T> {
  key: string;
  label: string;
  render?: (value: unknown, row: T) => React.ReactNode;
}

interface DataTableProps<T> {
  columns: Column<T>[];
  data: T[];
  loading?: boolean;
  onEdit?: (row: T) => void;
  onDelete?: (row: T) => void;
  keyField?: string;
}

function DataTable<T extends Record<string, unknown>>({
  columns,
  data,
  loading = false,
  onEdit,
  onDelete,
  keyField = 'id',
}: DataTableProps<T>) {
  if (loading) {
    return <div style={{ textAlign: 'center', padding: '2rem', color: '#6b7280' }}>Loading...</div>;
  }

  return (
    <div style={{ overflowX: 'auto', borderRadius: '0.5rem', border: '1px solid #e2e8f0' }}>
      <table style={{ width: '100%', borderCollapse: 'collapse', background: 'white' }}>
        <thead>
          <tr style={{ background: '#f1f5f9' }}>
            {columns.map((col) => (
              <th key={col.key} style={{
                padding: '0.75rem 1rem',
                textAlign: 'left',
                fontSize: '0.75rem',
                fontWeight: 600,
                color: '#374151',
                textTransform: 'uppercase',
                letterSpacing: '0.05em',
              }}>
                {col.label}
              </th>
            ))}
            {(onEdit || onDelete) && (
              <th style={{ padding: '0.75rem 1rem', textAlign: 'right', fontSize: '0.75rem', fontWeight: 600, color: '#374151', textTransform: 'uppercase' }}>
                Actions
              </th>
            )}
          </tr>
        </thead>
        <tbody>
          {data.length === 0 ? (
            <tr>
              <td colSpan={columns.length + (onEdit || onDelete ? 1 : 0)} style={{ padding: '2rem', textAlign: 'center', color: '#9ca3af' }}>
                No records found
              </td>
            </tr>
          ) : (
            data.map((row, idx) => (
              <tr key={String(row[keyField] ?? idx)} style={{ borderTop: '1px solid #e2e8f0' }}>
                {columns.map((col) => (
                  <td key={col.key} style={{ padding: '0.75rem 1rem', fontSize: '0.875rem', color: '#374151' }}>
                    {col.render ? col.render(row[col.key], row) : String(row[col.key] ?? '')}
                  </td>
                ))}
                {(onEdit || onDelete) && (
                  <td style={{ padding: '0.75rem 1rem', textAlign: 'right' }}>
                    {onEdit && (
                      <button
                        onClick={() => onEdit(row)}
                        style={{ marginRight: '0.5rem', padding: '0.25rem 0.625rem', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '0.25rem', cursor: 'pointer', fontSize: '0.75rem' }}
                      >
                        Edit
                      </button>
                    )}
                    {onDelete && (
                      <button
                        onClick={() => onDelete(row)}
                        style={{ padding: '0.25rem 0.625rem', background: '#ef4444', color: 'white', border: 'none', borderRadius: '0.25rem', cursor: 'pointer', fontSize: '0.75rem' }}
                      >
                        Delete
                      </button>
                    )}
                  </td>
                )}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}

export default DataTable;
