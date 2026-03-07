import React from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Trash2 } from 'lucide-react';
import { ordersApi } from '../../api/orders';
import Modal from '../../components/ui/Modal';
import Input from '../../components/ui/Input';
import Button from '../../components/ui/Button';
import toast from 'react-hot-toast';

const schema = z.object({
  customer_name: z.string().min(1, 'Required'),
  customer_email: z.string().email('Invalid email'),
  items: z.array(z.object({
    product_id: z.number().int().positive('Required'),
    quantity: z.number().int().positive('Min 1'),
    unit_price: z.number().positive('Required'),
  })).min(1, 'At least one item'),
});
type FormData = z.infer<typeof schema>;

interface Props { isOpen: boolean; onClose: () => void; }

const OrderModal: React.FC<Props> = ({ isOpen, onClose }) => {
  const queryClient = useQueryClient();
  const { register, handleSubmit, control, reset, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { customer_name: '', customer_email: '', items: [{ product_id: 0, quantity: 1, unit_price: 0 }] },
  });
  const { fields, append, remove } = useFieldArray({ control, name: 'items' });

  const mutation = useMutation({
    mutationFn: ordersApi.create,
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['orders'] }); toast.success('Order created'); reset(); onClose(); },
    onError: (e: any) => toast.error(e.response?.data?.message || 'Failed to create order'),
  });

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="New Order" size="2xl"
      footer={<><Button variant="outline" onClick={onClose} disabled={mutation.isPending}>Cancel</Button><Button onClick={handleSubmit((d) => mutation.mutate(d))} isLoading={mutation.isPending}>Create Order</Button></>}>
      <form className="space-y-4" onSubmit={handleSubmit((d) => mutation.mutate(d))}>
        <div className="grid grid-cols-2 gap-4">
          <Input label="Customer Name" required error={errors.customer_name?.message} {...register('customer_name')} />
          <Input label="Customer Email" type="email" required error={errors.customer_email?.message} {...register('customer_email')} />
        </div>
        <div>
          <div className="flex items-center justify-between mb-2">
            <label className="text-sm font-medium text-gray-700">Order Items</label>
            <Button type="button" variant="outline" size="sm" leftIcon={<Plus className="h-3.5 w-3.5" />} onClick={() => append({ product_id: 0, quantity: 1, unit_price: 0 })}>Add Item</Button>
          </div>
          <div className="space-y-2">
            {fields.map((field, index) => (
              <div key={field.id} className="grid grid-cols-12 gap-2 items-end">
                <div className="col-span-4"><Input label={index === 0 ? 'Product ID' : ''} type="number" placeholder="Product ID" error={(errors.items?.[index]?.product_id as any)?.message} {...register(`items.${index}.product_id`, { valueAsNumber: true })} /></div>
                <div className="col-span-3"><Input label={index === 0 ? 'Qty' : ''} type="number" min="1" error={(errors.items?.[index]?.quantity as any)?.message} {...register(`items.${index}.quantity`, { valueAsNumber: true })} /></div>
                <div className="col-span-4"><Input label={index === 0 ? 'Unit Price' : ''} type="number" step="0.01" error={(errors.items?.[index]?.unit_price as any)?.message} {...register(`items.${index}.unit_price`, { valueAsNumber: true })} /></div>
                <div className="col-span-1 pb-0.5"><Button type="button" variant="ghost" size="sm" className="text-red-500 hover:bg-red-50 w-full" onClick={() => remove(index)}><Trash2 className="h-4 w-4" /></Button></div>
              </div>
            ))}
          </div>
          {errors.items?.message && <p className="text-xs text-red-600 mt-1">{errors.items.message}</p>}
        </div>
      </form>
    </Modal>
  );
};
export default OrderModal;
