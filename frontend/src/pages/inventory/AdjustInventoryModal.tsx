import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { inventoryApi } from '../../api/inventory';
import { Inventory } from '../../types';
import Modal from '../../components/ui/Modal';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Button from '../../components/ui/Button';
import toast from 'react-hot-toast';

const schema = z.object({
  type: z.enum(['in', 'out', 'adjustment']),
  quantity: z.number().int().positive('Must be positive'),
  reference: z.string().optional(),
  notes: z.string().optional(),
});
type FormData = z.infer<typeof schema>;

interface Props { isOpen: boolean; onClose: () => void; inventory: Inventory; }

const AdjustInventoryModal: React.FC<Props> = ({ isOpen, onClose, inventory }) => {
  const queryClient = useQueryClient();
  const { register, handleSubmit, reset, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { type: 'in', quantity: 1, reference: '', notes: '' },
  });

  const mutation = useMutation({
    mutationFn: (d: FormData) => inventoryApi.adjust(inventory.id, d),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['inventory'] }); toast.success('Inventory adjusted'); reset(); onClose(); },
    onError: (e: any) => toast.error(e.response?.data?.message || 'Adjustment failed'),
  });

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={`Adjust — ${inventory.product?.name || `#${inventory.product_id}`}`}
      footer={<><Button variant="outline" onClick={onClose} disabled={mutation.isPending}>Cancel</Button><Button onClick={handleSubmit((d) => mutation.mutate(d))} isLoading={mutation.isPending}>Apply</Button></>}>
      <div className="mb-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-600">
        Current stock: <span className="font-semibold text-gray-900">{inventory.quantity}</span> · Reserved: <span className="font-semibold">{inventory.reserved_quantity}</span>
      </div>
      <form className="space-y-4" onSubmit={handleSubmit((d) => mutation.mutate(d))}>
        <Select label="Type" required options={[{ value: 'in', label: 'Stock In' }, { value: 'out', label: 'Stock Out' }, { value: 'adjustment', label: 'Adjustment' }]} error={errors.type?.message} {...register('type')} />
        <Input label="Quantity" type="number" min="1" required error={errors.quantity?.message} {...register('quantity', { valueAsNumber: true })} />
        <Input label="Reference" placeholder="PO-001 or SO-001" error={errors.reference?.message} {...register('reference')} />
        <Input label="Notes" placeholder="Optional notes..." error={errors.notes?.message} {...register('notes')} />
      </form>
    </Modal>
  );
};
export default AdjustInventoryModal;
