<template>
  <div class="home">
    <header class="hero">
      <h1>AutoERP</h1>
      <p>Enterprise Resource Planning System</p>
      <div class="cta">
        <router-link to="/login" class="btn btn-primary">Login</router-link>
        <router-link to="/dashboard" class="btn btn-secondary">Dashboard</router-link>
      </div>
    </header>

    <section class="features">
      <h2>Key Features</h2>
      <div class="feature-grid">
        <div class="feature-card">
          <h3>📦 Inventory Management</h3>
          <p>Complete inventory tracking with append-only stock ledger</p>
        </div>
        <div class="feature-card">
          <h3>🏢 Multi-Tenancy</h3>
          <p>Isolated tenant data with complete security</p>
        </div>
        <div class="feature-card">
          <h3>👥 IAM</h3>
          <p>Role-based access control with fine-grained permissions</p>
        </div>
        <div class="feature-card">
          <h3>📊 Analytics</h3>
          <p>Real-time reporting and business intelligence</p>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.home {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.hero {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 4rem 2rem;
  text-align: center;
}

.hero h1 {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.hero p {
  font-size: 1.25rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.cta {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.btn {
  padding: 0.75rem 2rem;
  border-radius: 0.5rem;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-primary {
  background: white;
  color: #667eea;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 2px solid white;
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.3);
}

.features {
  padding: 4rem 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.features h2 {
  text-align: center;
  font-size: 2rem;
  margin-bottom: 3rem;
  color: #333;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
}

.feature-card {
  padding: 2rem;
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s;
}

.feature-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.feature-card h3 {
  margin-bottom: 1rem;
  color: #667eea;
}

.feature-card p {
  color: #666;
  line-height: 1.6;
}
</style>
