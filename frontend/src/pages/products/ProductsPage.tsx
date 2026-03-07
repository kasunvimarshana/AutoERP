import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Search, Pencil, Trash2 } from 'lucide-react';
import { productsApi } from '../../api/products';
import { usePagination } from '../../hooks/usePagination';
import { Product } from '../../types';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Table, { Column } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import Badge from '../../components/ui/Badge';
import ProductModal from './ProductModal';
import toast from 'react-hot-toast';
import { formatCurrency, getProductStatusVariant, formatStatus } from '../../utils/formatters';

const ProductsPage: React.FC = () => {
  const queryClient = useQueryClient();
  const { page, perPage, setPage } = usePagination(15);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['products', { page, perPage, search, status }],
    queryFn: () => productsApi.list({ page, per_page: perPage, search, status: status || undefined }),
  });

  const deleteMutation = useMutation({
    mutationFn: productsApi.delete,
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['products'] }); toast.success('Product deleted'); setDeleteConfirm(null); },
    onError: (err: any) => toast.error(err.response?.data?.message || 'Delete failed'),
  });

  const columns: Column<Product>[] = [
    { key: 'name', header: 'Product', render: (row) => (
      <div><p className="font-medium text-gray-900">{row.name}</p><p className="text-xs text-gray-500">{row.sku}</p></div>
    )},
    { key: 'category', header: 'Category', render: (row) => <span className="text-sm text-gray-600">{row.category?.name || '—'}</span> },
    { key: 'price', header: 'Price', render: (row) => <span className="font-medium">{formatCurrency(row.price)}</span> },
    { key: 'status', header: 'Status', render: (row) => <Badge variant={getProductStatusVariant(row.status)}>{formatStatus(row.status)}</Badge> },
    { key: 'actions', header: 'Actions', className: 'text-right', render: (row) => (
      <div className="flex items-center justify-end gap-2">
        <Button variant="ghost" size="sm" leftIcon={<Pencil className="h-3.5 w-3.5" />} onClick={() => { setEditingProduct(row); setIsModalOpen(true); }}>Edit</Button>
        {deleteConfirm === row.id ? (
          <><Button variant="danger" size="sm" isLoading={deleteMutation.isPending} onClick={() => deleteMutation.mutate(row.id)}>Confirm</Button>
          <Button variant="secondary" size="sm" onClick={() => setDeleteConfirm(null)}>Cancel</Button></>
        ) : (
          <Button variant="ghost" size="sm" className="text-red-600 hover:bg-red-50" leftIcon={<Trash2 className="h-3.5 w-3.5" />} onClick={() => setDeleteConfirm(row.id)}>Delete</Button>
        )}
      </div>
    )},
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold text-gray-900">Products</h1><p className="text-sm text-gray-500 mt-1">Manage your product catalog</p></div>
        <Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => setIsModalOpen(true)}>Add Product</Button>
      </div>
      <Card noPadding>
        <div className="p-4 border-b border-gray-100 flex gap-3 flex-wrap">
          <Input placeholder="Search products..." leftAddon={<Search className="h-4 w-4" />} value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} className="max-w-xs" />
          <Select options={[{ value: 'active', label: 'Active' }, { value: 'inactive', label: 'Inactive' }]} placeholder="All Statuses" value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} className="w-40" />
        </div>
        <Table columns={columns} data={data?.data || []} isLoading={isLoading} rowKey={(row) => row.id} emptyMessage="No products found." />
        {data && data.last_page > 1 && <Pagination currentPage={data.current_page} lastPage={data.last_page} total={data.total} perPage={data.per_page} onPageChange={setPage} />}
      </Card>
      <ProductModal isOpen={isModalOpen} onClose={() => { setIsModalOpen(false); setEditingProduct(null); }} editingProduct={editingProduct} />
    </div>
  );
};
export default ProductsPage;
