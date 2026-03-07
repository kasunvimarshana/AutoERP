import { useQuery } from '@tanstack/react-query';
import { inventoryService } from '../services/inventoryService';

/**
 * Inventory stock levels page.
 */
export default function InventoryPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['inventory'],
    queryFn:  () => inventoryService.listInventory(),
  });

  return (
    <div>
      <h2 style={{ fontSize: 22, fontWeight: 700, marginBottom: 24 }}>Inventory</h2>

      <div style={{ background: '#fff', borderRadius: 10, boxShadow: '0 1px 4px rgba(0,0,0,0.06)', overflow: 'hidden' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead>
            <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e2e8f0' }}>
              {['Product', 'Available', 'Reserved', 'Reorder Threshold', 'Location', 'Status'].map(h => (
                <th key={h} style={{ padding: '12px 16px', textAlign: 'left', fontSize: 13, fontWeight: 600, color: '#64748b' }}>{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr><td colSpan={6} style={{ padding: 24, textAlign: 'center', color: '#94a3b8' }}>Loading…</td></tr>
            ) : data?.data?.map(item => {
              const isLow = item.quantity_available <= item.reorder_threshold;
              return (
                <tr key={item.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                  <td style={{ padding: '12px 16px', fontWeight: 500, fontFamily: 'monospace', fontSize: 13 }}>{item.product_id.slice(0, 8)}…</td>
                  <td style={{ padding: '12px 16px', fontWeight: 700, color: isLow ? '#dc2626' : '#16a34a' }}>{item.quantity_available}</td>
                  <td style={{ padding: '12px 16px', color: '#f59e0b', fontWeight: 600 }}>{item.quantity_reserved}</td>
                  <td style={{ padding: '12px 16px', color: '#64748b' }}>{item.reorder_threshold}</td>
                  <td style={{ padding: '12px 16px', color: '#64748b' }}>{item.warehouse_location ?? '–'}</td>
                  <td style={{ padding: '12px 16px' }}>
                    {isLow ? (
                      <span style={{ background: '#fef2f2', color: '#dc2626', padding: '3px 10px', borderRadius: 12, fontSize: 12, fontWeight: 600 }}>Low Stock</span>
                    ) : (
                      <span style={{ background: '#dcfce7', color: '#16a34a', padding: '3px 10px', borderRadius: 12, fontSize: 12, fontWeight: 600 }}>OK</span>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}
