import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import pricingApi, { type CreatePriceListPayload, type CreateDiscountRulePayload } from '@/api/pricing'

/**
 * PricingPage — pricing and discount management overview.
 *
 * Displays all price lists and discount rules for the current tenant.
 * All financial values use BCMath-safe string representations
 * matching the backend's arbitrary-precision arithmetic.
 *
 * Includes Create and Delete actions for both price lists and discount rules.
 */
export default function PricingPage() {
  const queryClient = useQueryClient()
  const [showPriceListForm, setShowPriceListForm] = useState(false)
  const [showDiscountForm, setShowDiscountForm] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)

  const {
    data: priceListData,
    isLoading: loadingLists,
    isError: errorLists,
  } = useQuery({
    queryKey: ['pricing', 'price-lists'],
    queryFn: () => pricingApi.listPriceLists({ per_page: 20 }),
  })

  const {
    data: discountData,
    isLoading: loadingDiscounts,
    isError: errorDiscounts,
  } = useQuery({
    queryKey: ['pricing', 'discount-rules'],
    queryFn: () => pricingApi.listDiscountRules({ per_page: 20 }),
  })

  const createPriceListMutation = useMutation({
    mutationFn: (payload: CreatePriceListPayload) => pricingApi.createPriceList(payload),
    onSuccess: () => {
      setFormSuccess('Price list created successfully.')
      setFormError(null)
      setShowPriceListForm(false)
      queryClient.invalidateQueries({ queryKey: ['pricing', 'price-lists'] })
    },
    onError: () => {
      setFormError('Failed to create price list. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const deletePriceListMutation = useMutation({
    mutationFn: (id: number) => pricingApi.deletePriceList(id),
    onSuccess: () => {
      setFormSuccess('Price list deleted successfully.')
      setFormError(null)
      queryClient.invalidateQueries({ queryKey: ['pricing', 'price-lists'] })
    },
    onError: () => {
      setFormError('Failed to delete price list.')
      setFormSuccess(null)
    },
  })

  const createDiscountRuleMutation = useMutation({
    mutationFn: (payload: CreateDiscountRulePayload) => pricingApi.createDiscountRule(payload),
    onSuccess: () => {
      setFormSuccess('Discount rule created successfully.')
      setFormError(null)
      setShowDiscountForm(false)
      queryClient.invalidateQueries({ queryKey: ['pricing', 'discount-rules'] })
    },
    onError: () => {
      setFormError('Failed to create discount rule. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const deleteDiscountRuleMutation = useMutation({
    mutationFn: (id: number) => pricingApi.deleteDiscountRule(id),
    onSuccess: () => {
      setFormSuccess('Discount rule deleted successfully.')
      setFormError(null)
      queryClient.invalidateQueries({ queryKey: ['pricing', 'discount-rules'] })
    },
    onError: () => {
      setFormError('Failed to delete discount rule.')
      setFormSuccess(null)
    },
  })

  const handleCreatePriceList = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: CreatePriceListPayload = {
      name: formData.get('name') as string,
      currency_code: formData.get('currency_code') as string,
      is_active: formData.get('is_active') === 'on',
    }
    createPriceListMutation.mutate(payload)
  }

  const handleDeletePriceList = (id: number, name: string) => {
    if (!window.confirm(`Delete price list "${name}"? This cannot be undone.`)) return
    setFormError(null)
    setFormSuccess(null)
    deletePriceListMutation.mutate(id)
  }

  const handleCreateDiscountRule = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const minQty = formData.get('min_quantity') as string
    const tier = formData.get('customer_tier') as string
    const payload: CreateDiscountRulePayload = {
      name: formData.get('name') as string,
      discount_type: formData.get('discount_type') as 'percentage' | 'flat',
      discount_value: formData.get('discount_value') as string,
      min_quantity: minQty || undefined,
      customer_tier: tier || undefined,
      is_active: formData.get('is_active') === 'on',
    }
    createDiscountRuleMutation.mutate(payload)
  }

  const handleDeleteDiscountRule = (id: number, name: string) => {
    if (!window.confirm(`Delete discount rule "${name}"? This cannot be undone.`)) return
    setFormError(null)
    setFormSuccess(null)
    deleteDiscountRuleMutation.mutate(id)
  }

  const priceLists = priceListData?.data?.data ?? []
  const discountRules = discountData?.data?.data ?? []
  const isLoading = loadingLists || loadingDiscounts

  return (
    <section aria-label="Pricing & Discounts">
      <h1>Pricing &amp; Discounts</h1>
      <p>Rule-based pricing, discount management, and price calculation engine.</p>

      {isLoading && <p role="status">Loading pricing data…</p>}
      {(errorLists || errorDiscounts) && (
        <p role="alert" className="error">Failed to load pricing data. Please try again.</p>
      )}

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}

      {!loadingLists && !errorLists && (
        <>
          <h2>Price Lists</h2>

          <div style={{ marginBottom: '1rem' }}>
            <button onClick={() => { setShowPriceListForm(!showPriceListForm); setShowDiscountForm(false) }} aria-expanded={showPriceListForm}>
              {showPriceListForm ? 'Cancel' : 'Create Price List'}
            </button>
          </div>

          {showPriceListForm && (
            <form onSubmit={handleCreatePriceList} aria-label="Create price list" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
              <label>
                Name
                <input name="name" type="text" required placeholder="e.g. Retail USD" />
              </label>
              <label>
                Currency Code
                <input name="currency_code" type="text" required placeholder="e.g. USD" maxLength={10} />
              </label>
              <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                <input name="is_active" type="checkbox" defaultChecked />
                Active
              </label>
              <button type="submit" disabled={createPriceListMutation.isPending}>
                {createPriceListMutation.isPending ? 'Creating…' : 'Create Price List'}
              </button>
            </form>
          )}

          <table aria-label="Price Lists">
            <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Name</th>
                <th scope="col">Currency</th>
                <th scope="col">Active</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {priceLists.length === 0 ? (
                <tr>
                  <td colSpan={5}>No price lists found.</td>
                </tr>
              ) : (
                priceLists.map((list) => (
                  <tr key={list.id}>
                    <td>{list.id}</td>
                    <td>{list.name}</td>
                    <td>{list.currency_code}</td>
                    <td>{list.is_active ? 'Yes' : 'No'}</td>
                    <td>
                      <button
                        onClick={() => handleDeletePriceList(list.id, list.name)}
                        disabled={deletePriceListMutation.isPending}
                        aria-label={`Delete price list ${list.name}`}
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </>
      )}

      {!loadingDiscounts && !errorDiscounts && (
        <>
          <h2>Discount Rules</h2>

          <div style={{ marginBottom: '1rem' }}>
            <button onClick={() => { setShowDiscountForm(!showDiscountForm); setShowPriceListForm(false) }} aria-expanded={showDiscountForm}>
              {showDiscountForm ? 'Cancel' : 'Create Discount Rule'}
            </button>
          </div>

          {showDiscountForm && (
            <form onSubmit={handleCreateDiscountRule} aria-label="Create discount rule" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
              <label>
                Name
                <input name="name" type="text" required placeholder="e.g. Summer Sale 10%" />
              </label>
              <label>
                Discount Type
                <select name="discount_type" required defaultValue="">
                  <option value="" disabled>Select type…</option>
                  <option value="percentage">Percentage</option>
                  <option value="flat">Flat</option>
                </select>
              </label>
              <label>
                Discount Value
                <input name="discount_value" type="text" required placeholder="e.g. 10.00" pattern="^\d+(\.\d+)?$" />
              </label>
              <label>
                Min Quantity (optional)
                <input name="min_quantity" type="text" placeholder="e.g. 5" pattern="^\d+(\.\d+)?$" />
              </label>
              <label>
                Customer Tier (optional)
                <input name="customer_tier" type="text" placeholder="e.g. gold" />
              </label>
              <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                <input name="is_active" type="checkbox" defaultChecked />
                Active
              </label>
              <button type="submit" disabled={createDiscountRuleMutation.isPending}>
                {createDiscountRuleMutation.isPending ? 'Creating…' : 'Create Discount Rule'}
              </button>
            </form>
          )}

          <table aria-label="Discount Rules">
            <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Name</th>
                <th scope="col">Type</th>
                <th scope="col">Value</th>
                <th scope="col">Min Qty</th>
                <th scope="col">Active</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {discountRules.length === 0 ? (
                <tr>
                  <td colSpan={7}>No discount rules found.</td>
                </tr>
              ) : (
                discountRules.map((rule) => (
                  <tr key={rule.id}>
                    <td>{rule.id}</td>
                    <td>{rule.name}</td>
                    <td>{rule.discount_type}</td>
                    <td>{rule.discount_value}</td>
                    <td>{rule.min_quantity ?? '—'}</td>
                    <td>{rule.is_active ? 'Yes' : 'No'}</td>
                    <td>
                      <button
                        onClick={() => handleDeleteDiscountRule(rule.id, rule.name)}
                        disabled={deleteDiscountRuleMutation.isPending}
                        aria-label={`Delete discount rule ${rule.name}`}
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </>
      )}
    </section>
  )
}
