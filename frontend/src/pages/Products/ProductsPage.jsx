import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { format } from 'date-fns'
import toast from 'react-hot-toast'
import { PlusCircle, Pencil, Trash2, Package } from 'lucide-react'

import { productsApi } from '../../api/productsApi'
import SearchInput from '../../components/UI/SearchInput'
import Select from '../../components/UI/Select'
import Table from '../../components/UI/Table'
import Badge from '../../components/UI/Badge'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'
import Pagination from '../../components/UI/Pagination'

const SORT_OPTIONS = [
  { value: 'created_at', label: 'Created At' },
  { value: 'name', label: 'Name' },
  { value: 'price', label: 'Price' },
]

export default function ProductsPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [searchParams, setSearchParams] = useSearchParams()

  const search = searchParams.get('search') ?? ''
  const category = searchParams.get('category') ?? ''
  const page = Number(searchParams.get('page') ?? 1)
  const sort = searchParams.get('sort') ?? 'created_at'
  const direction = searchParams.get('direction') ?? 'desc'

  const [deletingId, setDeletingId] = useState(null)

  function updateParam(key, value) {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev)
      if (value) {
        next.set(key, value)
      } else {
        next.delete(key)
      }
      if (key !== 'page') next.set('page', '1')
      return next
    })
  }

  const { data, isLoading } = useQuery({
    queryKey: ['products', { search, category, page, per_page: 15, sort, direction }],
    queryFn: () =>
      productsApi.getProducts({ search, category, page, per_page: 15, sort, direction }),
    keepPreviousData: true,
  })

  const products = data?.data?.data ?? data?.data ?? []
  const meta = data?.data?.meta ?? {}
  const totalPages = meta.last_page ?? meta.total_pages ?? 1
  const totalItems = meta.total ?? products.length

  const deleteMutation = useMutation({
    mutationFn: (id) => productsApi.deleteProduct(id),
    onSuccess: () => {
      toast.success('Product deleted successfully')
      queryClient.invalidateQueries({ queryKey: ['products'] })
      setDeletingId(null)
    },
    onError: (err) => {
      toast.error(err?.response?.data?.message ?? 'Failed to delete product')
      setDeletingId(null)
    },
  })

  function handleDelete(product) {
    if (!window.confirm(`Delete product "${product.name}"? This action cannot be undone.`)) return
    setDeletingId(product.id)
    deleteMutation.mutate(product.id)
  }

  const columns = [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'sku', label: 'SKU' },
    { key: 'category', label: 'Category', render: (val) => val ?? '—' },
    {
      key: 'price',
      label: 'Price',
      sortable: true,
      render: (val) => (val != null ? `$${Number(val).toFixed(2)}` : '—'),
    },
    {
      key: 'cost_price',
      label: 'Cost Price',
      render: (val) => (val != null ? `$${Number(val).toFixed(2)}` : '—'),
    },
    {
      key: 'is_active',
      label: 'Active',
      render: (val) => (
        <Badge variant={val ? 'success' : 'default'}>{val ? 'Active' : 'Inactive'}</Badge>
      ),
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (_, row) => (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => navigate(`/products/${row.id}/edit`)}
            title="Edit product"
          >
            <Pencil className="w-4 h-4" />
          </Button>
          <Button
            variant="danger"
            size="sm"
            loading={deletingId === row.id}
            onClick={() => handleDelete(row)}
            title="Delete product"
          >
            <Trash2 className="w-4 h-4" />
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <Package className="w-6 h-6 text-green-600" />
            Products
          </h1>
          <p className="mt-1 text-sm text-gray-500">Manage your product catalog</p>
        </div>
        <Button variant="primary" onClick={() => navigate('/products/create')}>
          <PlusCircle className="w-4 h-4" />
          Create Product
        </Button>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div className="flex flex-wrap gap-3">
          <SearchInput
            value={search}
            onChange={(v) => updateParam('search', v)}
            placeholder="Search by name or SKU…"
            className="flex-1 min-w-[200px]"
          />
          <Input
            name="category"
            type="text"
            placeholder="Filter by category"
            value={category}
            onChange={(e) => updateParam('category', e.target.value)}
            className="w-44"
          />
          <Select
            name="sort"
            value={sort}
            options={SORT_OPTIONS}
            onChange={(e) => updateParam('sort', e.target.value)}
            className="w-40"
          />
          <Button
            variant="ghost"
            size="sm"
            onClick={() => updateParam('direction', direction === 'asc' ? 'desc' : 'asc')}
          >
            {direction === 'asc' ? '↑ Asc' : '↓ Desc'}
          </Button>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <Table
          columns={columns}
          data={products}
          loading={isLoading}
          sortKey={sort}
          sortDir={direction}
          onSort={(key, dir) => {
            updateParam('sort', key)
            updateParam('direction', dir)
          }}
          emptyMessage="No products found. Try adjusting your filters."
        />
        {!isLoading && (
          <div className="border-t border-gray-100 px-4">
            <Pagination
              currentPage={page}
              totalPages={totalPages}
              totalItems={totalItems}
              perPage={15}
              onPageChange={(p) => updateParam('page', String(p))}
            />
          </div>
        )}
      </div>
    </div>
  )
}
