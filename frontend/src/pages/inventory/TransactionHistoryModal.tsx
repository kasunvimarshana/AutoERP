import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { inventoryApi } from '../../api/inventory';
import { Inventory } from '../../types';
import Modal from '../../components/ui/Modal';
import Badge from '../../components/ui/Badge';
import Spinner from '../../components/ui/Spinner';
import Button from '../../components/ui/Button';
import { formatDateTime } from '../../utils/formatters';

interface Props { isOpen: boolean; onClose: () => void; inventory: Inventory; }

const typeVariant = (t: string): 'success' | 'danger' | 'warning' | 'info' | 'gray' => {
  if (t === 'in') return 'success';
  if (t === 'out') return 'danger';
  if (t === 'adjustment') return 'warning';
  return 'gray';
};

const TransactionHistoryModal: React.FC<Props> = ({ isOpen, onClose, inventory }) => {
  const { data, isLoading } = useQuery({
    queryKey: ['inventory-transactions', inventory.id],
    queryFn: () => inventoryApi.transactions(inventory.id),
    enabled: isOpen,
  });

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={`History — ${inventory.product?.name || `#${inventory.product_id}`}`} size="lg"
      footer={<Button variant="outline" onClick={onClose}>Close</Button>}>
      {isLoading ? <div className="flex justify-center py-8"><Spinner /></div> : (
        <div className="space-y-2 max-h-80 overflow-y-auto">
          {(!data || data.length === 0) ? <p className="text-center text-sm text-gray-400 py-8">No transactions found.</p> : data.map((t) => (
            <div key={t.id} className="flex items-center justify-between p-3 rounded-lg bg-gray-50">
              <div>
                <div className="flex items-center gap-2">
                  <Badge variant={typeVariant(t.type)} className="capitalize">{t.type}</Badge>
                  <span className="text-sm font-semibold text-gray-800">{t.type === 'out' ? '-' : '+'}{t.quantity}</span>
                </div>
                {t.reference && <p className="text-xs text-gray-500 mt-1">Ref: {t.reference}</p>}
                {t.notes && <p className="text-xs text-gray-400">{t.notes}</p>}
              </div>
              <span className="text-xs text-gray-400">{formatDateTime(t.created_at)}</span>
            </div>
          ))}
        </div>
      )}
    </Modal>
  );
};
export default TransactionHistoryModal;
