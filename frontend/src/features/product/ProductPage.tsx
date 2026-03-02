import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import productApi, { type Product, type CreateProductPayload, type UpdateProductPayload } from '@/api/product'

const PRODUCT_TYPES: Product['type'][] = [
  'physical', 'consumable', 'service', 'digital', 'bundle', 'composite', 'variant',
]

/**
 * ProductPage — product catalog management overview.
 *
 * Displays a paginated list of products with type, SKU, UOM,
 * and active status. Supports all product types including
 * physical, consumable, service, digital, bundle, composite, and variant.
 *
 * Includes Create, Edit, and Delete actions.
 */
export default function ProductPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [editingProduct, setEditingProduct] = useState<Product | null>(null)
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['product', 'products'],
    queryFn: () => productApi.listProducts({ per_page: 20 }),
  })

  const createMutation = useMutation({
    mutationFn: (payload: CreateProductPayload) => productApi.createProduct(payload),
    onSuccess: () => {
      setFormSuccess('Product created successfully.')
      setFormError(null)
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['product', 'products'] })
    },
    onError: () => {
      setFormError('Failed to create product. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateProductPayload }) =>
      productApi.updateProduct(id, payload),
    onSuccess: () => {
      setFormSuccess('Product updated successfully.')
      setFormError(null)
      setEditingProduct(null)
      queryClient.invalidateQueries({ queryKey: ['product', 'products'] })
    },
    onError: () => {
      setFormError('Failed to update product. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => productApi.deleteProduct(id),
    onSuccess: () => {
      setFormSuccess('Product deleted successfully.')
      setFormError(null)
      queryClient.invalidateQueries({ queryKey: ['product', 'products'] })
    },
    onError: () => {
      setFormError('Failed to delete product.')
      setFormSuccess(null)
    },
  })

  const handleCreate = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: CreateProductPayload = {
      name: formData.get('name') as string,
      sku: formData.get('sku') as string,
      type: formData.get('type') as Product['type'],
      uom: formData.get('uom') as string,
      is_active: formData.get('is_active') === 'on',
    }
    createMutation.mutate(payload)
  }

  const handleEdit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    if (!editingProduct) return
    setFormError(null)
    setFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: UpdateProductPayload = {
      name: formData.get('name') as string,
      sku: formData.get('sku') as string,
      type: formData.get('type') as Product['type'],
      uom: formData.get('uom') as string,
      is_active: formData.get('is_active') === 'on',
    }
    updateMutation.mutate({ id: editingProduct.id, payload })
  }

  const handleDelete = (product: Product) => {
    if (!window.confirm(`Delete product "${product.name}"? This cannot be undone.`)) return
    setFormError(null)
    setFormSuccess(null)
    deleteMutation.mutate(product.id)
  }

  const items = data?.data?.data ?? []

  return (
    <section aria-label="Product Catalog">
      <h1>Product Catalog</h1>
      <p>Product types, SKU management, UOM conversions, and variant support.</p>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => { setShowForm(!showForm); setEditingProduct(null) }} aria-expanded={showForm}>
          {showForm ? 'Cancel' : 'Create Product'}
        </button>
      </div>

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}

      {showForm && (
        <form onSubmit={handleCreate} aria-label="Create product" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. Paracetamol 500mg" />
          </label>
          <label>
            SKU
            <input name="sku" type="text" required placeholder="e.g. PARA-500" />
          </label>
          <label>
            Type
            <select name="type" required defaultValue="">
              <option value="" disabled>Select type…</option>
              {PRODUCT_TYPES.map((t) => (
                <option key={t} value={t}>{t}</option>
              ))}
            </select>
          </label>
          <label>
            UOM
            <input name="uom" type="text" required placeholder="e.g. each" />
          </label>
          <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <input name="is_active" type="checkbox" defaultChecked />
            Active
          </label>
          <button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating…' : 'Create Product'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading products…</p>}
      {isError && <p role="alert" className="error">Failed to load products. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Products">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Name</th>
              <th scope="col">SKU</th>
              <th scope="col">Type</th>
              <th scope="col">UOM</th>
              <th scope="col">Active</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 ? (
              <tr>
                <td colSpan={7}>No products found.</td>
              </tr>
            ) : (
              items.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.name}</td>
                  <td>{item.sku}</td>
                  <td>{item.type}</td>
                  <td>{item.uom}</td>
                  <td>{item.is_active ? 'Yes' : 'No'}</td>
                  <td>
                    <button
                      onClick={() => { setEditingProduct(item); setShowForm(false); setFormError(null); setFormSuccess(null) }}
                      aria-label={`Edit product ${item.name}`}
                    >
                      Edit
                    </button>
                    {' '}
                    <button
                      onClick={() => handleDelete(item)}
                      disabled={deleteMutation.isPending}
                      aria-label={`Delete product ${item.name}`}
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}

      {editingProduct && (
        <form onSubmit={handleEdit} aria-label={`Edit product ${editingProduct.name}`} style={{ marginTop: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <h2>Edit Product: {editingProduct.name}</h2>
          <label>
            Name
            <input name="name" type="text" required defaultValue={editingProduct.name} />
          </label>
          <label>
            SKU
            <input name="sku" type="text" required defaultValue={editingProduct.sku} />
          </label>
          <label>
            Type
            <select name="type" required defaultValue={editingProduct.type}>
              {PRODUCT_TYPES.map((t) => (
                <option key={t} value={t}>{t}</option>
              ))}
            </select>
          </label>
          <label>
            UOM
            <input name="uom" type="text" required defaultValue={editingProduct.uom} />
          </label>
          <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <input name="is_active" type="checkbox" defaultChecked={editingProduct.is_active} />
            Active
          </label>
          <div style={{ display: 'flex', gap: '0.5rem' }}>
            <button type="submit" disabled={updateMutation.isPending}>
              {updateMutation.isPending ? 'Saving…' : 'Save Changes'}
            </button>
            <button type="button" onClick={() => setEditingProduct(null)}>
              Cancel
            </button>
          </div>
        </form>
      )}
    </section>
  )
}
