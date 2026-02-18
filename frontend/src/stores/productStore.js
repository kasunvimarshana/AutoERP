import { defineStore } from 'pinia'
import { productService } from '../services/productService'

export const useProductStore = defineStore('product', {
  state: () => ({
    products: [],
    currentProduct: null,
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1
    }
  }),

  getters: {
    getProductById: (state) => (id) => {
      return state.products.find(product => product.id === id)
    }
  },

  actions: {
    async fetchProducts(params = {}) {
      this.loading = true
      this.error = null
      
      try {
        const response = await productService.getProducts(params)
        this.products = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          per_page: response.data.per_page,
          total: response.data.total,
          last_page: response.data.last_page
        }
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch products'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchProduct(id) {
      this.loading = true
      this.error = null
      
      try {
        const response = await productService.getProduct(id)
        this.currentProduct = response.data
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch product'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createProduct(data) {
      this.loading = true
      this.error = null
      
      try {
        const response = await productService.createProduct(data)
        this.products.unshift(response.data)
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create product'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateProduct(id, data) {
      this.loading = true
      this.error = null
      
      try {
        const response = await productService.updateProduct(id, data)
        const index = this.products.findIndex(p => p.id === id)
        if (index !== -1) {
          this.products[index] = response.data
        }
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update product'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteProduct(id) {
      this.loading = true
      this.error = null
      
      try {
        const response = await productService.deleteProduct(id)
        this.products = this.products.filter(p => p.id !== id)
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete product'
        throw error
      } finally {
        this.loading = false
      }
    },

    async searchProducts(query) {
      this.loading = true
      this.error = null
      
      try {
        const response = await productService.searchProducts(query)
        this.products = response.data
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to search products'
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
