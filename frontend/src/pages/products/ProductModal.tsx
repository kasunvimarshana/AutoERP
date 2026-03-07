import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { productsApi } from '../../api/products';
import { Product } from '../../types';
import Modal from '../../components/ui/Modal';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Button from '../../components/ui/Button';
import toast from 'react-hot-toast';

const schema = z.object({
  name: z.string().min(1, 'Required'),
  sku: z.string().min(1, 'Required'),
  description: z.string().optional(),
  price: z.number().positive('Must be positive'),
  category_id: z.number().int().positive('Required'),
  status: z.enum(['active', 'inactive']),
});
type FormData = z.infer<typeof schema>;

interface Props { isOpen: boolean; onClose: () => void; editingProduct: Product | null; }

const ProductModal: React.FC<Props> = ({ isOpen, onClose, editingProduct }) => {
  const queryClient = useQueryClient();
  const isEdit = !!editingProduct;
  const { data: categories = [] } = useQuery({ queryKey: ['categories'], queryFn: productsApi.categories });
  const { register, handleSubmit, reset, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', sku: '', description: '', price: 0, category_id: 0, status: 'active' },
  });

  useEffect(() => {
    if (editingProduct) reset({ name: editingProduct.name, sku: editingProduct.sku, description: editingProduct.description, price: editingProduct.price, category_id: editingProduct.category_id, status: editingProduct.status });
    else reset({ name: '', sku: '', description: '', price: 0, category_id: 0, status: 'active' });
  }, [editingProduct, reset]);

  const createMutation = useMutation({ mutationFn: productsApi.create, onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['products'] }); toast.success('Product created'); onClose(); }, onError: (e: any) => toast.error(e.response?.data?.message || 'Failed') });
  const updateMutation = useMutation({ mutationFn: (d: FormData) => productsApi.update(editingProduct!.id, d), onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['products'] }); toast.success('Product updated'); onClose(); }, onError: (e: any) => toast.error(e.response?.data?.message || 'Failed') });
  const onSubmit = (d: FormData) => isEdit ? updateMutation.mutate(d) : createMutation.mutate(d);
  const isPending = createMutation.isPending || updateMutation.isPending;

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={isEdit ? 'Edit Product' : 'Add Product'} footer={<><Button variant="outline" onClick={onClose} disabled={isPending}>Cancel</Button><Button onClick={handleSubmit(onSubmit)} isLoading={isPending}>{isEdit ? 'Save' : 'Create'}</Button></>}>
      <form className="space-y-4" onSubmit={handleSubmit(onSubmit)}>
        <Input label="Product Name" required error={errors.name?.message} {...register('name')} />
        <Input label="SKU" required error={errors.sku?.message} {...register('sku')} />
        <Input label="Description" error={errors.description?.message} {...register('description')} />
        <Input label="Price" type="number" step="0.01" required error={errors.price?.message} {...register('price', { valueAsNumber: true })} />
        <Select label="Category" required options={categories.map(c => ({ value: c.id, label: c.name }))} placeholder="Select category" error={errors.category_id?.message} {...register('category_id', { valueAsNumber: true })} />
        <Select label="Status" required options={[{ value: 'active', label: 'Active' }, { value: 'inactive', label: 'Inactive' }]} error={errors.status?.message} {...register('status')} />
      </form>
    </Modal>
  );
};
export default ProductModal;
