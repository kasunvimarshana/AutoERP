<template>
  <div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-gray-950 font-sans select-none" @keydown.esc="handleEsc">
    <!-- ── Left Panel: Product catalog ─────────────────────────────────────── -->
    <div class="flex flex-col w-0 flex-1 min-w-0 border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
      <!-- Topbar -->
      <header class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-800 shrink-0">
        <RouterLink
          to="/pos"
          class="flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
          aria-label="Back to POS dashboard"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
          POS
        </RouterLink>
        <span class="text-gray-300 dark:text-gray-700 text-sm" aria-hidden="true">|</span>
        <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">
          Terminal
          <span v-if="sessionInfo" class="ml-1 text-xs font-normal text-gray-400 dark:text-gray-500">· Session {{ sessionInfo.id?.slice(0, 8) }}</span>
        </span>
        <div class="flex-1" />
        <!-- Session status badge -->
        <span
          :class="sessionInfo?.status === 'open' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'"
          class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold"
        >
          <span class="w-1.5 h-1.5 rounded-full" :class="sessionInfo?.status === 'open' ? 'bg-emerald-500' : 'bg-amber-500'" aria-hidden="true" />
          {{ sessionInfo?.status ?? 'unknown' }}
        </span>
      </header>

      <!-- Search + Category filter -->
      <div class="px-4 py-3 space-y-2 border-b border-gray-100 dark:border-gray-800 shrink-0">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 6.5 6.5a7.5 7.5 0 0 0 10.65 10.65z" /></svg>
          <input
            ref="searchInputRef"
            v-model="productSearch"
            type="search"
            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 pl-9 pr-4 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            placeholder="Search products… (F3)"
            @input="onSearchInput"
          />
        </div>
        <!-- Category pills -->
        <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
          <button
            type="button"
            :class="selectedCategory === null ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="shrink-0 rounded-full px-3 py-1 text-xs font-medium transition-colors"
            @click="selectedCategory = null"
          >All</button>
          <button
            v-for="cat in pos.catalogCategories"
            :key="cat.id"
            type="button"
            :class="selectedCategory === cat.id ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="shrink-0 rounded-full px-3 py-1 text-xs font-medium transition-colors"
            @click="selectedCategory = cat.id"
          >{{ cat.name }}</button>
        </div>
      </div>

      <!-- Product grid -->
      <div class="flex-1 overflow-y-auto p-4">
        <!-- Loading skeleton -->
        <div v-if="pos.catalogLoading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
          <div v-for="i in 8" :key="i" class="rounded-xl bg-gray-100 dark:bg-gray-800 animate-pulse h-28" />
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-600">
          <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
          <p class="text-sm font-medium">No products found</p>
          <p class="text-xs mt-1">Try adjusting your search or category filter</p>
        </div>

        <!-- Product cards -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
          <button
            v-for="product in filteredProducts"
            :key="product.id"
            type="button"
            class="group relative flex flex-col rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 text-left transition-all hover:border-indigo-400 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 active:scale-95"
            @click="handleProductClick(product)"
          >
            <!-- Product image placeholder -->
            <div class="w-full aspect-square rounded-lg bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 flex items-center justify-center mb-2 overflow-hidden">
              <svg class="w-8 h-8 text-indigo-300 dark:text-indigo-600 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
            </div>
            <p class="text-xs font-semibold text-gray-900 dark:text-white line-clamp-2 leading-snug">{{ product.name }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 font-mono">{{ product.sku }}</p>
            <p class="mt-1.5 text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(product.unit_price) }}</p>
            <!-- Track indicators -->
            <div class="flex gap-1 mt-1">
              <span v-if="product.track_lots" class="inline-block rounded bg-amber-100 dark:bg-amber-900/40 px-1 py-0.5 text-[10px] font-semibold text-amber-700 dark:text-amber-400">BATCH</span>
              <span v-if="product.track_serials" class="inline-block rounded bg-blue-100 dark:bg-blue-900/40 px-1 py-0.5 text-[10px] font-semibold text-blue-700 dark:text-blue-400">SERIAL</span>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- ── Right Panel: Cart ────────────────────────────────────────────────── -->
    <div class="w-[380px] xl:w-[420px] shrink-0 flex flex-col bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800">
      <!-- Cart header -->
      <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between shrink-0">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Current Order</h2>
        <div class="flex items-center gap-2">
          <span class="text-xs text-gray-400 dark:text-gray-500">{{ cartLines.length }} line{{ cartLines.length !== 1 ? 's' : '' }}</span>
          <button
            v-if="cartLines.length > 0"
            type="button"
            class="rounded px-2 py-0.5 text-xs font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
            @click="confirmClearCart"
          >Clear</button>
        </div>
      </div>

      <!-- Cart lines -->
      <div class="flex-1 overflow-y-auto">
        <!-- Empty cart -->
        <div v-if="cartLines.length === 0" class="flex flex-col items-center justify-center h-full py-8 text-gray-300 dark:text-gray-700">
          <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
          <p class="text-xs font-medium">Cart is empty</p>
          <p class="text-[11px] mt-0.5">Click a product to add it</p>
        </div>

        <!-- Line items -->
        <ul v-else class="divide-y divide-gray-100 dark:divide-gray-800">
          <li
            v-for="(line, idx) in cartLines"
            :key="line._key"
            :class="selectedLineIdx === idx ? 'bg-indigo-50 dark:bg-indigo-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800/40'"
            class="px-4 py-2.5 cursor-pointer transition-colors"
            @click="selectedLineIdx = idx"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ line.product_name }}</p>
                <p v-if="line.variant_name" class="text-xs text-indigo-500 dark:text-indigo-400 truncate">{{ line.variant_name }}</p>
                <p v-if="line.lot_number" class="text-xs text-amber-600 dark:text-amber-400 truncate font-mono">Lot: {{ line.lot_number }}</p>
                <div class="flex items-center gap-3 mt-1">
                  <!-- Quantity control -->
                  <div class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button
                      type="button"
                      class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-sm font-bold"
                      aria-label="Decrease quantity"
                      @click.stop="adjustQty(idx, -1)"
                    >−</button>
                    <input
                      :value="line.quantity"
                      type="number"
                      min="0.01"
                      step="any"
                      class="w-12 text-center text-sm font-semibold text-gray-900 dark:text-white bg-transparent border-x border-gray-200 dark:border-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 py-0.5"
                      aria-label="Quantity"
                      @click.stop
                      @change.stop="e => setQty(idx, e.target.value)"
                    />
                    <button
                      type="button"
                      class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-sm font-bold"
                      aria-label="Increase quantity"
                      @click.stop="adjustQty(idx, 1)"
                    >+</button>
                  </div>
                  <!-- Per-line discount -->
                  <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-400">Disc</span>
                    <input
                      :value="line.discount"
                      type="number"
                      min="0"
                      max="100"
                      step="0.01"
                      class="w-14 rounded border border-gray-200 dark:border-gray-700 bg-transparent px-1.5 py-0.5 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-center"
                      placeholder="0"
                      aria-label="Line discount %"
                      @click.stop
                      @change.stop="e => setLineDiscount(idx, e.target.value)"
                    />
                    <span class="text-xs text-gray-400">%</span>
                  </div>
                  <!-- Per-line tax -->
                  <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-400">Tax</span>
                    <input
                      :value="line.tax_rate"
                      type="number"
                      min="0"
                      max="100"
                      step="0.01"
                      class="w-14 rounded border border-gray-200 dark:border-gray-700 bg-transparent px-1.5 py-0.5 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-center"
                      placeholder="0"
                      aria-label="Line tax %"
                      @click.stop
                      @change.stop="e => setLineTax(idx, e.target.value)"
                    />
                    <span class="text-xs text-gray-400">%</span>
                  </div>
                </div>
              </div>
              <div class="text-right shrink-0">
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(lineTotal(line)) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ formatCurrency(line.unit_price) }} ea</p>
                <button
                  type="button"
                  class="mt-1 text-red-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                  aria-label="Remove line"
                  @click.stop="removeLine(idx)"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Customer + Discount code -->
      <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 space-y-2 shrink-0">
        <!-- Customer ID -->
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
          <input
            v-model="customerId"
            type="text"
            class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
            placeholder="Customer ID (optional)"
          />
        </div>
        <!-- Discount code -->
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.169.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z" /></svg>
          <input
            v-model="discountCode"
            type="text"
            class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono uppercase tracking-widest"
            placeholder="Discount code"
            @input="handleDiscountCodeInput"
          />
          <button
            type="button"
            :disabled="!discountCode || pos.discountValidationLoading"
            class="shrink-0 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-40 transition-colors"
            @click="handleValidateDiscount"
          >{{ pos.discountValidationLoading ? '…' : 'Apply' }}</button>
        </div>
        <!-- Discount validation result -->
        <div v-if="pos.discountValidation" class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 px-3 py-1.5 flex items-center justify-between">
          <span class="text-xs text-emerald-700 dark:text-emerald-400 font-medium">
            ✓ {{ pos.discountValidation.name }}
            — saves {{ pos.discountValidation.type === 'percentage' ? Number(pos.discountValidation.value).toFixed(2) + '%' : formatCurrency(pos.discountValidation.value) }}
          </span>
          <button type="button" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 ml-2" aria-label="Remove discount" @click="removeDiscountCode">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
      </div>

      <!-- Order totals -->
      <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 space-y-1.5 bg-gray-50 dark:bg-gray-800/50 shrink-0 text-sm">
        <div class="flex justify-between text-gray-600 dark:text-gray-400">
          <span>Subtotal</span>
          <span>{{ formatCurrency(subtotal) }}</span>
        </div>
        <div v-if="discountAmount > 0" class="flex justify-between text-emerald-600 dark:text-emerald-400">
          <span>Discount</span>
          <span>− {{ formatCurrency(discountAmount) }}</span>
        </div>
        <div v-if="taxTotal > 0" class="flex justify-between text-gray-600 dark:text-gray-400">
          <span>Tax</span>
          <span>+ {{ formatCurrency(taxTotal) }}</span>
        </div>
        <div class="flex justify-between text-base font-bold text-gray-900 dark:text-white pt-1.5 border-t border-gray-200 dark:border-gray-700">
          <span>Total</span>
          <span>{{ formatCurrency(orderTotal) }}</span>
        </div>
      </div>

      <!-- Payment method -->
      <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 space-y-2 shrink-0">
        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Payment Method</label>
        <div class="grid grid-cols-4 gap-1.5">
          <button
            v-for="method in paymentMethods"
            :key="method.value"
            type="button"
            :class="paymentMethod === method.value ? 'bg-indigo-600 text-white border-indigo-600 dark:border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400'"
            class="flex flex-col items-center gap-0.5 rounded-xl border py-2 px-1 text-[11px] font-medium transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500"
            @click="paymentMethod = method.value"
          >
            <span v-html="method.icon" class="w-5 h-5" aria-hidden="true" />
            {{ method.label }}
          </button>
        </div>
        <!-- Cash tendered -->
        <div v-if="paymentMethod === 'cash'" class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Cash Tendered</label>
            <input
              v-model="cashTendered"
              type="number"
              min="0"
              step="0.01"
              class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="0.00"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Change</label>
            <div
              :class="changeAmount >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'"
              class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm font-bold"
            >
              {{ changeAmount !== null ? formatCurrency(changeAmount) : '—' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Place Order button -->
      <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-800 shrink-0">
        <div v-if="orderError" class="mb-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-xs text-red-700 dark:text-red-400">{{ orderError }}</div>
        <button
          type="button"
          :disabled="cartLines.length === 0 || pos.loading || (paymentMethod === 'cash' && (!cashTendered || changeAmount < 0))"
          class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          @click="handlePlaceOrder"
        >
          <span v-if="pos.loading">Processing…</span>
          <span v-else>Place Order · {{ formatCurrency(orderTotal) }}</span>
        </button>
        <p class="text-center text-xs text-gray-400 dark:text-gray-600 mt-2">Press <kbd class="font-mono bg-gray-100 dark:bg-gray-800 rounded px-1 py-0.5 text-[11px]">F12</kbd> to place order</p>
      </div>
    </div>

    <!-- ── Variant / Lot selection modal ──────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showVariantModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" role="dialog" aria-modal="true" :aria-labelledby="'variant-modal-title'">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showVariantModal = false" />
        <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <div>
              <h3 :id="'variant-modal-title'" class="text-base font-semibold text-gray-900 dark:text-white">{{ variantModalProduct?.name }}</h3>
              <p class="text-xs text-gray-400 mt-0.5">Select variant{{ (variantModalProduct?.track_lots || variantModalProduct?.track_serials) ? ' and batch/serial' : '' }}</p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close" @click="showVariantModal = false">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <div class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
            <!-- Variants list -->
            <div v-if="pos.catalogVariants.length > 0">
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Variants</label>
              <div v-if="pos.catalogVariantsLoading" class="space-y-2">
                <div v-for="i in 3" :key="i" class="h-10 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse" />
              </div>
              <div v-else class="space-y-1.5">
                <button
                  v-for="variant in pos.catalogVariants"
                  :key="variant.id"
                  type="button"
                  :class="selectedVariantId === variant.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300 bg-white dark:bg-gray-800'"
                  class="w-full flex items-center justify-between rounded-lg border px-3 py-2.5 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  @click="selectVariant(variant)"
                >
                  <div class="text-left">
                    <span class="font-medium text-gray-900 dark:text-white">{{ variant.name }}</span>
                    <span class="text-xs text-gray-400 ml-2 font-mono">{{ variant.sku }}</span>
                    <div v-if="variant.attributes" class="flex gap-1 mt-0.5 flex-wrap">
                      <span
                        v-for="(val, key) in variant.attributes"
                        :key="`${key}-${val}`"
                        class="rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:text-gray-300"
                      >{{ key }}: {{ val }}</span>
                    </div>
                  </div>
                  <span class="shrink-0 text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(variant.unit_price) }}</span>
                </button>
              </div>
            </div>

            <!-- Base product option (if no variant selected, or no variants) -->
            <div v-if="pos.catalogVariants.length === 0 && !pos.catalogVariantsLoading">
              <div class="rounded-lg border border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 px-3 py-2.5 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ variantModalProduct?.name }}</span>
                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(variantModalProduct?.unit_price) }}</span>
              </div>
            </div>

            <!-- Lots / serials -->
            <div v-if="variantModalProduct?.track_lots || variantModalProduct?.track_serials">
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                {{ variantModalProduct?.track_serials ? 'Serial Number' : 'Batch / Lot' }}
              </label>
              <div v-if="pos.catalogLotsLoading" class="space-y-2">
                <div v-for="i in 3" :key="i" class="h-10 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse" />
              </div>
              <div v-else-if="pos.catalogLots.length === 0" class="text-xs text-gray-400 dark:text-gray-600 py-2">No active lots/serials found for this product.</div>
              <div v-else class="space-y-1.5">
                <button
                  v-for="lot in pos.catalogLots"
                  :key="lot.id"
                  type="button"
                  :class="selectedLotId === lot.id ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-amber-300 bg-white dark:bg-gray-800'"
                  class="w-full flex items-center justify-between rounded-lg border px-3 py-2.5 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-amber-500"
                  @click="selectedLotId = selectedLotId === lot.id ? null : lot.id; selectedLotNumber = selectedLotId ? lot.lot_number : null"
                >
                  <div class="text-left">
                    <span class="font-mono font-medium text-gray-900 dark:text-white">{{ lot.lot_number }}</span>
                    <span v-if="lot.expiry_date" class="text-xs text-gray-400 ml-2">Exp: {{ lot.expiry_date }}</span>
                  </div>
                  <span class="text-xs text-gray-500 dark:text-gray-400">Qty: {{ lot.qty }}</span>
                </button>
              </div>
            </div>

            <!-- Quantity for modal -->
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Quantity</label>
              <div class="flex items-center gap-2">
                <button type="button" class="w-10 h-10 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center text-lg font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" @click="modalQty = Math.max(0.01, modalQty - 1)">−</button>
                <input
                  v-model="modalQty"
                  type="number"
                  min="0.01"
                  step="any"
                  class="flex-1 text-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <button type="button" class="w-10 h-10 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center text-lg font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" @click="modalQty++">+</button>
              </div>
            </div>
          </div>

          <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 flex gap-3">
            <button type="button" class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showVariantModal = false">Cancel</button>
            <button type="button" class="flex-1 rounded-xl bg-indigo-600 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 transition-colors" @click="addToCartFromModal">Add to Cart</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Order success modal ─────────────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />
        <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 p-8 text-center">
          <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Order Placed!</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Order <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ lastOrderNumber }}</span></p>
          <p v-if="lastChangeAmount > 0" class="text-sm text-gray-600 dark:text-gray-300 mb-4">Change: <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(lastChangeAmount) }}</span></p>
          <p v-else class="mb-4" />
          <button type="button" class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white hover:bg-indigo-700 transition-colors" @click="startNewOrder">New Order</button>
        </div>
      </div>
    </Teleport>

    <!-- ── Clear cart confirmation ────────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showClearConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="alertdialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50" @click="showClearConfirm = false" />
        <div class="relative w-full max-w-sm rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">Clear Cart?</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">All {{ cartLines.length }} item{{ cartLines.length !== 1 ? 's' : '' }} will be removed.</p>
          <div class="flex gap-3">
            <button type="button" class="flex-1 rounded-lg border border-gray-300 dark:border-gray-700 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showClearConfirm = false">Keep</button>
            <button type="button" class="flex-1 rounded-lg bg-red-600 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors" @click="executeClearCart">Clear All</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { usePosStore } from '@/stores/pos';
import { useFormatters } from '@/composables/useFormatters';

const route = useRoute();
const pos = usePosStore();
const { formatCurrency } = useFormatters();

const SEARCH_DEBOUNCE_MS = 300;

// ── Session info ───────────────────────────────────────────────────────────

const sessionInfo = computed(() => {
    const sid = route.params.sessionId;
    return pos.sessions.find(s => s.id === sid) ?? { id: sid, status: 'open' };
});

// ── Product catalog ────────────────────────────────────────────────────────

const productSearch = ref('');
const selectedCategory = ref(null);
const searchInputRef = ref(null);
let searchDebounce = null;

const filteredProducts = computed(() => {
    let list = pos.catalogProducts;
    if (selectedCategory.value) {
        list = list.filter(p => p.category_id === selectedCategory.value);
    }
    return list;
});

function onSearchInput() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        pos.fetchCatalogProducts({ search: productSearch.value || undefined, category_id: selectedCategory.value || undefined });
    }, SEARCH_DEBOUNCE_MS);
}

watch(selectedCategory, () => {
    pos.fetchCatalogProducts({ search: productSearch.value || undefined, category_id: selectedCategory.value || undefined });
});

// ── Cart state ─────────────────────────────────────────────────────────────

let lineKeyCounter = 0;
const cartLines = ref([]);
const selectedLineIdx = ref(null);
const customerId = ref('');
const discountCode = ref('');
const paymentMethod = ref('cash');
const cashTendered = ref('');
const orderError = ref(null);

const paymentMethods = [
    {
        value: 'cash',
        label: 'Cash',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>',
    },
    {
        value: 'card',
        label: 'Card',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>',
    },
    {
        value: 'digital_wallet',
        label: 'Wallet',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18-3a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6m18 0V5.25A2.25 2.25 0 0019.5 3h-15A2.25 2.25 0 002.25 5.25V6" /></svg>',
    },
    {
        value: 'credit',
        label: 'Credit',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z" /></svg>',
    },
];

// ── Totals ─────────────────────────────────────────────────────────────────

function lineTotal(line) {
    const qty = parseFloat(line.quantity) || 0;
    const price = parseFloat(line.unit_price) || 0;
    const disc = parseFloat(line.discount) || 0;
    const tax = parseFloat(line.tax_rate) || 0;
    const afterDiscount = price * (1 - disc / 100);
    return qty * afterDiscount * (1 + tax / 100);
}

const subtotal = computed(() => cartLines.value.reduce((s, l) => {
    const qty = parseFloat(l.quantity) || 0;
    const price = parseFloat(l.unit_price) || 0;
    const disc = parseFloat(l.discount) || 0;
    return s + qty * price * (1 - disc / 100);
}, 0));

const taxTotal = computed(() => cartLines.value.reduce((s, l) => {
    const qty = parseFloat(l.quantity) || 0;
    const price = parseFloat(l.unit_price) || 0;
    const disc = parseFloat(l.discount) || 0;
    const tax = parseFloat(l.tax_rate) || 0;
    return s + qty * price * (1 - disc / 100) * (tax / 100);
}, 0));

const discountAmount = computed(() => {
    const v = pos.discountValidation;
    if (!v) return 0;
    if (v.type === 'percentage') return subtotal.value * (parseFloat(v.value) / 100);
    return Math.min(parseFloat(v.value) || 0, subtotal.value);
});

const orderTotal = computed(() => Math.max(0, subtotal.value + taxTotal.value - discountAmount.value));

const changeAmount = computed(() => {
    const tendered = parseFloat(cashTendered.value);
    if (!cashTendered.value || isNaN(tendered)) return null;
    return tendered - orderTotal.value;
});

// ── Cart actions ───────────────────────────────────────────────────────────

function makeLineKey() { return ++lineKeyCounter; }

function handleProductClick(product) {
    orderError.value = null;
    // If product has variants or lot/serial tracking → open selection modal
    if (product.track_lots || product.track_serials) {
        openVariantModal(product);
        return;
    }
    // Check for variants asynchronously; if none found, add directly
    pos.fetchProductVariants(product.id).then(() => {
        if (pos.catalogVariants.length > 0) {
            openVariantModal(product);
        } else {
            addToCartDirect(product, null, null, null, 1);
        }
    }).catch(() => {
        // Fallback: add directly if variant fetch fails
        addToCartDirect(product, null, null, null, 1);
    });
}

function addToCartDirect(product, variant, lotId, lotNumber, qty) {
    const name = variant ? `${product.name} – ${variant.name}` : product.name;
    const productId = product.id;
    const price = variant ? parseFloat(variant.unit_price) : parseFloat(product.unit_price);

    // Check if same product/variant/lot already in cart → increment qty
    const existing = cartLines.value.find(l =>
        l.product_id === productId &&
        l.variant_id === (variant?.id ?? null) &&
        l.lot_id === (lotId ?? null),
    );
    if (existing) {
        existing.quantity = parseFloat(existing.quantity) + qty;
        return;
    }

    cartLines.value.push({
        _key: makeLineKey(),
        product_id: productId,
        variant_id: variant?.id ?? null,
        lot_id: lotId ?? null,
        lot_number: lotNumber ?? null,
        product_name: name,
        variant_name: variant?.name ?? null,
        quantity: qty,
        unit_price: price,
        discount: '',
        tax_rate: '',
    });
    selectedLineIdx.value = cartLines.value.length - 1;
}

function adjustQty(idx, delta) {
    const line = cartLines.value[idx];
    const newQty = Math.max(0.01, (parseFloat(line.quantity) || 1) + delta);
    cartLines.value[idx].quantity = newQty;
}

function setQty(idx, val) {
    const newQty = Math.max(0.01, parseFloat(val) || 1);
    cartLines.value[idx].quantity = newQty;
}

function setLineDiscount(idx, val) {
    cartLines.value[idx].discount = val === '' ? '' : Math.min(100, Math.max(0, parseFloat(val) || 0));
}

function setLineTax(idx, val) {
    cartLines.value[idx].tax_rate = val === '' ? '' : Math.min(100, Math.max(0, parseFloat(val) || 0));
}

function removeLine(idx) {
    cartLines.value.splice(idx, 1);
    if (selectedLineIdx.value >= cartLines.value.length) {
        selectedLineIdx.value = cartLines.value.length - 1;
    }
}

const showClearConfirm = ref(false);

function confirmClearCart() { showClearConfirm.value = true; }

function executeClearCart() {
    cartLines.value = [];
    selectedLineIdx.value = null;
    pos.clearDiscountValidation();
    discountCode.value = '';
    cashTendered.value = '';
    orderError.value = null;
    showClearConfirm.value = false;
}

// ── Discount handling ──────────────────────────────────────────────────────

function handleDiscountCodeInput(e) {
    discountCode.value = e.target.value.toUpperCase();
    pos.clearDiscountValidation();
}

async function handleValidateDiscount() {
    if (!discountCode.value) return;
    try {
        await pos.validateDiscountCode(discountCode.value.toUpperCase(), subtotal.value);
    } catch {
        // error is shown via pos.error
    }
}

function removeDiscountCode() {
    discountCode.value = '';
    pos.clearDiscountValidation();
}

// ── Variant modal ──────────────────────────────────────────────────────────

const showVariantModal = ref(false);
const variantModalProduct = ref(null);
const selectedVariantId = ref(null);
const selectedVariantObj = ref(null);
const selectedLotId = ref(null);
const selectedLotNumber = ref(null);
const modalQty = ref(1);

async function openVariantModal(product) {
    variantModalProduct.value = product;
    selectedVariantId.value = null;
    selectedVariantObj.value = null;
    selectedLotId.value = null;
    selectedLotNumber.value = null;
    modalQty.value = 1;
    showVariantModal.value = true;

    await Promise.all([
        pos.fetchProductVariants(product.id),
        (product.track_lots || product.track_serials) ? pos.fetchProductLots(product.id) : Promise.resolve(),
    ]);
}

function selectVariant(variant) {
    selectedVariantId.value = variant.id;
    selectedVariantObj.value = variant;
}

function addToCartFromModal() {
    if (!variantModalProduct.value) return;
    addToCartDirect(
        variantModalProduct.value,
        selectedVariantObj.value,
        selectedLotId.value,
        selectedLotNumber.value,
        parseFloat(modalQty.value) || 1,
    );
    showVariantModal.value = false;
}

// ── Place order ────────────────────────────────────────────────────────────

const showSuccessModal = ref(false);
const lastOrderNumber = ref('');
const lastChangeAmount = ref(0);

async function handlePlaceOrder() {
    orderError.value = null;
    const sessionId = route.params.sessionId;
    if (!sessionId) { orderError.value = 'No session ID in URL.'; return; }
    if (cartLines.value.length === 0) { orderError.value = 'Cart is empty.'; return; }

    const payload = {
        session_id: sessionId,
        payment_method: paymentMethod.value,
        lines: cartLines.value.map(l => {
            const line = {
                product_name: l.product_name,
                quantity: parseFloat(l.quantity),
                unit_price: parseFloat(l.unit_price),
            };
            if (l.product_id) line.product_id = l.product_id;
            if (l.discount !== '' && l.discount !== null) line.discount = parseFloat(l.discount);
            if (l.tax_rate !== '' && l.tax_rate !== null) line.tax_rate = parseFloat(l.tax_rate);
            return line;
        }),
    };
    if (customerId.value) payload.customer_id = customerId.value;
    if (paymentMethod.value === 'cash' && cashTendered.value !== '') {
        payload.cash_tendered = parseFloat(cashTendered.value);
    }
    if (discountCode.value) payload.discount_code = discountCode.value.toUpperCase();

    try {
        const order = await pos.placeOrder(payload);
        lastOrderNumber.value = order.number ?? order.id?.slice(0, 8);
        lastChangeAmount.value = order.change_amount ?? (changeAmount.value > 0 ? changeAmount.value : 0);
        showSuccessModal.value = true;
        executeClearCartSilent();
    } catch (e) {
        orderError.value = e.response?.data?.message ?? 'Failed to place order.';
    }
}

function executeClearCartSilent() {
    cartLines.value = [];
    selectedLineIdx.value = null;
    pos.clearDiscountValidation();
    discountCode.value = '';
    cashTendered.value = '';
}

function startNewOrder() {
    showSuccessModal.value = false;
    orderError.value = null;
    nextTick(() => searchInputRef.value?.focus());
}

// ── Keyboard shortcuts ─────────────────────────────────────────────────────

function handleKeyDown(e) {
    if (e.key === 'F3') {
        e.preventDefault();
        searchInputRef.value?.focus();
    }
    if (e.key === 'F12') {
        e.preventDefault();
        if (cartLines.value.length > 0 && !pos.loading) handlePlaceOrder();
    }
}

function handleEsc() {
    if (showVariantModal.value) { showVariantModal.value = false; return; }
    if (showClearConfirm.value) { showClearConfirm.value = false; return; }
    if (showSuccessModal.value) { startNewOrder(); }
}

// ── Init ───────────────────────────────────────────────────────────────────

onMounted(() => {
    pos.fetchCatalogProducts();
    pos.fetchCatalogCategories();
    if (pos.sessions.length === 0) pos.fetchSessions();
    document.addEventListener('keydown', handleKeyDown);
    nextTick(() => searchInputRef.value?.focus());
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown);
    clearTimeout(searchDebounce);
});
</script>
