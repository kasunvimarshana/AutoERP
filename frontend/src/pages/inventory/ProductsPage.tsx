import React, { useState } from 'react';
import { Plus, Search, RefreshCw, Pencil, Trash2 } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useProducts, useDeleteProduct } from '@/hooks/useProducts';
import DataTable from '@/components/common/DataTable';
import Pagination from '@/components/common/Pagination';
import Modal from '@/components/common/Modal';
import ConfirmDialog from '@/components/common/ConfirmDialog';
import ProductForm from '@/components/inventory/ProductForm';
import StockBadge from '@/components/inventory/StockBadge';
import RoleGuard from '@/components/auth/RoleGuard';
import { useCreateProduct, useUpdateProduct } from '@/hooks/useProducts';
import type { Product, TableColumn, ProductFormData } from '@/types';

const ProductsPage: React.FC = () => {
  const navigate = useNavigate();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [sortBy, setSortBy] = useState('name');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');
  const [createOpen, setCreateOpen] = useState(false);
  const [editProduct, setEditProduct] = useState<Product | null>(null);
  const [deleteProduct, setDeleteProduct] = useState<Product | null>(null);

  const searchTimeout = React.useRef<ReturnType<typeof setTimeout>>();

  const { data, isLoading, refetch } = useProducts({
    page,
    per_page: 15,
    search: debouncedSearch,
    sort_by: sortBy,
    sort_direction: sortDir,
  });

  const createMutation = useCreateProduct();
  const updateMutation = useUpdateProduct();
  const deleteMutation = useDeleteProduct();

  const handleSearch = (value: string) => {
    setSearch(value);
    clearTimeout(searchTimeout.current);
    searchTimeout.current = setTimeout(() => {
      setDebouncedSearch(value);
      setPage(1);
    }, 400);
  };

  const handleSort = (key: string) => {
    if (sortBy === key) {
      setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    } else {
      setSortBy(key);
      setSortDir('asc');
    }
  };

  const columns: TableColumn<Product>[] = [
    { key: 'sku', label: 'SKU', sortable: true, className: 'font-mono text-xs w-28' },
    { key: 'name', label: 'Product Name', sortable: true },
    { key: 'category', label: 'Category', render: (v) => (v as string) ?? '—' },
    {
      key: 'price',
      label: 'Price',
      sortable: true,
      className: 'text-right',
      render: (v) => `$${Number(v).toFixed(2)}`,
    },
    {
      key: 'available_quantity',
      label: 'Stock',
      sortable: true,
      render: (_, row) => <StockBadge product={row} />,
    },
    {
      key: 'is_active',
      label: 'Status',
      render: (v) =>
        v ? (
          <span className="text-xs text-green-600 font-medium">Active</span>
        ) : (
          <span className="text-xs text-gray-400">Inactive</span>
        ),
    },
    {
      key: 'id',
      label: '',
      className: 'w-24',
      render: (_, row) => (
        <div className="flex items-center gap-1">
          <RoleGuard permissions={['products.update']}>
            <button
              onClick={(e) => { e.stopPropagation(); setEditProduct(row); }}
              className="p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-blue-50 transition-colors"
              title="Edit"
            >
              <Pencil size={14} />
            </button>
          </RoleGuard>
          <RoleGuard permissions={['products.delete']}>
            <button
              onClick={(e) => { e.stopPropagation(); setDeleteProduct(row); }}
              className="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
              title="Delete"
            >
              <Trash2 size={14} />
            </button>
          </RoleGuard>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Inventory</h1>
          <p className="text-sm text-gray-500 mt-0.5">
            {data?.meta.total ?? 0} products total
          </p>
        </div>
        <RoleGuard permissions={['products.create']}>
          <button
            onClick={() => setCreateOpen(true)}
            className="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-xl text-sm font-medium hover:bg-primary-700 transition-colors shadow-sm"
          >
            <Plus size={16} />
            New Product
          </button>
        </RoleGuard>
      </div>

      {/* Filters */}
      <div className="flex items-center gap-3">
        <div className="relative flex-1 max-w-sm">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            value={search}
            onChange={(e) => handleSearch(e.target.value)}
            placeholder="Search products…"
            className="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          />
        </div>
        <button
          onClick={() => refetch()}
          className="p-2 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors"
          title="Refresh"
        >
          <RefreshCw size={16} />
        </button>
      </div>

      <DataTable
        columns={columns}
        data={data?.data ?? []}
        isLoading={isLoading}
        sortBy={sortBy}
        sortDirection={sortDir}
        onSort={handleSort}
        keyExtractor={(row) => row.id}
        emptyMessage="No products found. Create your first product."
        onRowClick={(row) => navigate(`/inventory/${row.id}`)}
      />

      {data?.meta && (
        <Pagination meta={data.meta} onPageChange={setPage} />
      )}

      {/* Create Modal */}
      <Modal
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="New Product"
        size="lg"
      >
        <ProductForm
          onSubmit={async (formData: ProductFormData) => {
            await createMutation.mutateAsync(formData);
            setCreateOpen(false);
          }}
          isLoading={createMutation.isPending}
          onCancel={() => setCreateOpen(false)}
        />
      </Modal>

      {/* Edit Modal */}
      <Modal
        open={!!editProduct}
        onClose={() => setEditProduct(null)}
        title="Edit Product"
        size="lg"
      >
        {editProduct && (
          <ProductForm
            initialData={editProduct}
            onSubmit={async (formData: ProductFormData) => {
              await updateMutation.mutateAsync({ id: editProduct.id, data: formData });
              setEditProduct(null);
            }}
            isLoading={updateMutation.isPending}
            onCancel={() => setEditProduct(null)}
          />
        )}
      </Modal>

      {/* Delete Confirm */}
      <ConfirmDialog
        open={!!deleteProduct}
        onClose={() => setDeleteProduct(null)}
        onConfirm={async () => {
          if (deleteProduct) {
            await deleteMutation.mutateAsync(deleteProduct.id);
            setDeleteProduct(null);
          }
        }}
        title="Delete Product"
        message={`Are you sure you want to delete "${deleteProduct?.name}"? This action cannot be undone.`}
        confirmLabel="Delete"
        confirmVariant="danger"
        isLoading={deleteMutation.isPending}
      />
    </div>
  );
};

export default ProductsPage;
