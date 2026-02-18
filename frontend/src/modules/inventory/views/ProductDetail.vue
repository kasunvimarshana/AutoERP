<template>
  <div class="product-detail">
    <header class="page-header">
      <h1>Product Details</h1>
      <router-link to="/inventory/products" class="btn-back">
        ← Back to Products
      </router-link>
    </header>

    <div class="page-content">
      <div class="detail-container">
        <p>Product details coming soon...</p>
        <p>This will show complete product information and stock history.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-detail {
  flex: 1;
  background: #f5f7fa;
}

.page-header {
  background: white;
  padding: 1.5rem 2rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-back {
  padding: 0.75rem 1.5rem;
  background: #f8f9fa;
  color: #333;
  text-decoration: none;
  border-radius: 0.5rem;
  font-weight: 600;
}

.page-content {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.detail-container {
  background: white;
  padding: 2rem;
  border-radius: 0.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
