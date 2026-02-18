<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Analytics Hub</h1>
      <div class="header-actions">
        <button @click="scheduleReport" class="btn btn-secondary">
          <ClockIcon class="icon" />
          Schedule Report
        </button>
        <button @click="exportData" class="btn btn-secondary">
          <ArrowDownTrayIcon class="icon" />
          Export
        </button>
        <button @click="refreshAnalytics" class="btn btn-primary">
          <ArrowPathIcon class="icon" />
          Refresh
        </button>
      </div>
    </div>
    
    <!-- Global Filters -->
    <div class="filters-card">
      <div class="filters-row">
        <div class="filter-group">
          <label>Date Range</label>
          <select v-model="filters.dateRange" @change="handleDateRangeChange" class="filter-select">
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year">This Year</option>
            <option value="ytd">Year to Date</option>
            <option value="last-week">Last Week</option>
            <option value="last-month">Last Month</option>
            <option value="last-quarter">Last Quarter</option>
            <option value="last-year">Last Year</option>
            <option value="custom">Custom Range</option>
          </select>
        </div>
        
        <div v-if="filters.dateRange === 'custom'" class="filter-group">
          <label>Start Date</label>
          <input v-model="filters.startDate" type="date" class="filter-input" />
        </div>
        
        <div v-if="filters.dateRange === 'custom'" class="filter-group">
          <label>End Date</label>
          <input v-model="filters.endDate" type="date" class="filter-input" />
        </div>
        
        <div class="filter-group">
          <label>Compare With</label>
          <select v-model="filters.comparison" class="filter-select">
            <option value="">No Comparison</option>
            <option value="previous">Previous Period</option>
            <option value="last-year">Same Period Last Year</option>
            <option value="budget">Budget</option>
          </select>
        </div>
        
        <button @click="applyFilters" class="btn btn-primary btn-filter">
          Apply Filters
        </button>
      </div>
    </div>
    
    <!-- Analytics Tabs -->
    <div class="tabs-container">
      <div class="tabs-header">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="['tab-button', { active: activeTab === tab.id }]"
          @click="activeTab = tab.id"
        >
          <component :is="tab.icon" class="tab-icon" />
          {{ tab.label }}
        </button>
      </div>
      
      <div class="tabs-content">
        <!-- Sales Analytics Tab -->
        <div v-if="activeTab === 'sales'" class="tab-panel">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Loading sales analytics...</p>
          </div>
          
          <div v-else>
            <!-- KPI Cards -->
            <div class="kpi-grid">
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #dbeafe; color: #3b82f6;">
                  <CurrencyDollarIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Total Revenue</p>
                  <p class="kpi-value">${{ formatNumber(salesData.totalRevenue) }}</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ salesData.revenueGrowth }}% vs previous period
                  </p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #dcfce7; color: #16a34a;">
                  <ShoppingCartIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Total Orders</p>
                  <p class="kpi-value">{{ formatNumber(salesData.totalOrders) }}</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ salesData.ordersGrowth }}% vs previous period
                  </p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #fef3c7; color: #d97706;">
                  <ChartBarIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Average Order Value</p>
                  <p class="kpi-value">${{ formatNumber(salesData.avgOrderValue) }}</p>
                  <p class="kpi-trend negative">
                    <ArrowTrendingDownIcon class="trend-icon" />
                    {{ salesData.aovGrowth }}% vs previous period
                  </p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #e0e7ff; color: #6366f1;">
                  <FunnelIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Conversion Rate</p>
                  <p class="kpi-value">{{ salesData.conversionRate }}%</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ salesData.conversionGrowth }}% vs previous period
                  </p>
                </div>
              </div>
            </div>
            
            <!-- Charts Section -->
            <div class="charts-section">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Revenue Trend</h3>
                  <div class="chart-actions">
                    <button class="btn-icon" title="Download">
                      <ArrowDownTrayIcon class="icon-small" />
                    </button>
                  </div>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Line Chart: Daily/Weekly/Monthly Revenue Trend</p>
                    <p class="placeholder-note">X-axis: Time periods | Y-axis: Revenue ($)</p>
                    <p class="placeholder-note">Shows revenue over time with comparison line if enabled</p>
                  </div>
                </div>
              </div>
              
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Sales by Product Category</h3>
                  <div class="chart-actions">
                    <button class="btn-icon" title="Download">
                      <ArrowDownTrayIcon class="icon-small" />
                    </button>
                  </div>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartPieIcon class="placeholder-icon" />
                    <p class="placeholder-text">Pie Chart: Revenue Distribution by Category</p>
                    <p class="placeholder-note">Shows percentage breakdown of sales by product category</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="charts-section">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Sales by Customer Segment</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: Revenue by Customer Segment</p>
                    <p class="placeholder-note">Compares sales across different customer types</p>
                  </div>
                </div>
              </div>
              
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Sales by Region</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: Revenue by Geographic Region</p>
                    <p class="placeholder-note">Shows regional sales performance</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Conversion Funnel -->
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Sales Conversion Funnel</h3>
              </div>
              <div class="chart-body">
                <div class="funnel-container">
                  <div class="funnel-stage" style="width: 100%;">
                    <div class="funnel-bar" style="background: #3b82f6;">
                      <span class="funnel-label">Quotations</span>
                      <span class="funnel-value">1,234 (100%)</span>
                    </div>
                  </div>
                  <div class="funnel-stage" style="width: 75%;">
                    <div class="funnel-bar" style="background: #10b981;">
                      <span class="funnel-label">Orders</span>
                      <span class="funnel-value">925 (75%)</span>
                    </div>
                  </div>
                  <div class="funnel-stage" style="width: 60%;">
                    <div class="funnel-bar" style="background: #f59e0b;">
                      <span class="funnel-label">Invoices</span>
                      <span class="funnel-value">740 (60%)</span>
                    </div>
                  </div>
                  <div class="funnel-stage" style="width: 55%;">
                    <div class="funnel-bar" style="background: #8b5cf6;">
                      <span class="funnel-label">Payments Received</span>
                      <span class="funnel-value">679 (55%)</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Top Performers -->
            <div class="charts-section">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Top 10 Products</h3>
                </div>
                <div class="chart-body">
                  <div class="table-container">
                    <table class="data-table">
                      <thead>
                        <tr>
                          <th>Rank</th>
                          <th>Product</th>
                          <th>Revenue</th>
                          <th>Units Sold</th>
                          <th>Growth</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="i in 10" :key="i">
                          <td>{{ i }}</td>
                          <td>Product {{ i }}</td>
                          <td>${{ formatNumber(50000 - i * 2000) }}</td>
                          <td>{{ formatNumber(500 - i * 20) }}</td>
                          <td>
                            <span :class="['trend-badge', i % 2 === 0 ? 'positive' : 'negative']">
                              {{ i % 2 === 0 ? '+' : '-' }}{{ Math.floor(Math.random() * 30) }}%
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Top 10 Customers</h3>
                </div>
                <div class="chart-body">
                  <div class="table-container">
                    <table class="data-table">
                      <thead>
                        <tr>
                          <th>Rank</th>
                          <th>Customer</th>
                          <th>Revenue</th>
                          <th>Orders</th>
                          <th>Growth</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="i in 10" :key="i">
                          <td>{{ i }}</td>
                          <td>Customer {{ i }}</td>
                          <td>${{ formatNumber(80000 - i * 4000) }}</td>
                          <td>{{ formatNumber(50 - i * 2) }}</td>
                          <td>
                            <span :class="['trend-badge', i % 3 !== 0 ? 'positive' : 'negative']">
                              {{ i % 3 !== 0 ? '+' : '-' }}{{ Math.floor(Math.random() * 40) }}%
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Sales Forecast -->
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Sales Forecast (Next 3 Months)</h3>
              </div>
              <div class="chart-body">
                <div class="chart-placeholder">
                  <ChartBarIcon class="placeholder-icon" />
                  <p class="placeholder-text">Area Chart: Historical vs Forecasted Sales</p>
                  <p class="placeholder-note">Shows actual sales data with projected trend line and confidence intervals</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Inventory Analytics Tab -->
        <div v-if="activeTab === 'inventory'" class="tab-panel">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Loading inventory analytics...</p>
          </div>
          
          <div v-else>
            <!-- KPI Cards -->
            <div class="kpi-grid">
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #dbeafe; color: #3b82f6;">
                  <CubeIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Total Stock Value</p>
                  <p class="kpi-value">${{ formatNumber(inventoryData.totalValue) }}</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ inventoryData.valueGrowth }}% vs previous period
                  </p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #dcfce7; color: #16a34a;">
                  <ArrowPathIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Inventory Turnover</p>
                  <p class="kpi-value">{{ inventoryData.turnoverRate }}x</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ inventoryData.turnoverGrowth }}% vs previous period
                  </p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #fef3c7; color: #d97706;">
                  <ExclamationTriangleIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Low Stock Items</p>
                  <p class="kpi-value">{{ inventoryData.lowStockCount }}</p>
                  <p class="kpi-trend">items below reorder point</p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #fee2e2; color: #dc2626;">
                  <ClockIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Slow Moving Items</p>
                  <p class="kpi-value">{{ inventoryData.slowMovingCount }}</p>
                  <p class="kpi-trend">items with low turnover</p>
                </div>
              </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-section">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Stock Level Trends</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Line Chart: Stock Levels Over Time</p>
                    <p class="placeholder-note">Shows inventory quantity trends by category</p>
                  </div>
                </div>
              </div>
              
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Stock Valuation by Category</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartPieIcon class="placeholder-icon" />
                    <p class="placeholder-text">Pie Chart: Inventory Value Distribution</p>
                    <p class="placeholder-note">Shows percentage of total inventory value by category</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="charts-section">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Inventory Turnover by Category</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: Turnover Rate by Category</p>
                    <p class="placeholder-note">Compares how quickly different categories sell</p>
                  </div>
                </div>
              </div>
              
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Stock by Warehouse</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: Stock Distribution by Location</p>
                    <p class="placeholder-note">Shows inventory quantities across warehouses</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Slow Moving Items -->
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Slow Moving Items (Turnover < 2x/year)</h3>
              </div>
              <div class="chart-body">
                <div class="table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Stock Quantity</th>
                        <th>Stock Value</th>
                        <th>Last Sale Date</th>
                        <th>Days in Stock</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="i in 5" :key="i">
                        <td>Product {{ i }}</td>
                        <td>{{ formatNumber(100 + i * 50) }}</td>
                        <td>${{ formatNumber(5000 + i * 1000) }}</td>
                        <td>{{ getRandomDate() }}</td>
                        <td><span class="badge badge-warning">{{ 180 + i * 30 }} days</span></td>
                        <td>
                          <button class="btn-sm btn-secondary">Review</button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            
            <!-- Reorder Predictions -->
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Reorder Predictions (Next 30 Days)</h3>
              </div>
              <div class="chart-body">
                <div class="table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Current Stock</th>
                        <th>Avg Daily Usage</th>
                        <th>Predicted Stockout</th>
                        <th>Suggested Reorder Qty</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="i in 5" :key="i">
                        <td>Product {{ i }}</td>
                        <td>{{ formatNumber(50 + i * 10) }}</td>
                        <td>{{ 5 + i }} units/day</td>
                        <td><span class="badge badge-danger">{{ 5 + i * 2 }} days</span></td>
                        <td>{{ formatNumber(200 + i * 50) }}</td>
                        <td>
                          <button class="btn-sm btn-primary">Create PO</button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            
            <!-- Warehouse Efficiency -->
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Warehouse Efficiency Metrics</h3>
              </div>
              <div class="chart-body">
                <div class="chart-placeholder">
                  <ChartBarIcon class="placeholder-icon" />
                  <p class="placeholder-text">Multi-Bar Chart: Warehouse Performance Metrics</p>
                  <p class="placeholder-note">Shows pick/pack times, accuracy rates, and space utilization by warehouse</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Finance Analytics Tab -->
        <div v-if="activeTab === 'finance'" class="tab-panel">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Loading finance analytics...</p>
          </div>
          
          <div v-else>
            <!-- KPI Cards -->
            <div class="kpi-grid">
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #dbeafe; color: #3b82f6;">
                  <BanknotesIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Net Cash Flow</p>
                  <p class="kpi-value">${{ formatNumber(financeData.netCashFlow) }}</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ financeData.cashFlowGrowth }}% vs previous period
                  </p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #dcfce7; color: #16a34a;">
                  <DocumentTextIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Accounts Receivable</p>
                  <p class="kpi-value">${{ formatNumber(financeData.accountsReceivable) }}</p>
                  <p class="kpi-trend">Average age: {{ financeData.arAvgAge }} days</p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #fef3c7; color: #d97706;">
                  <ReceiptPercentIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Accounts Payable</p>
                  <p class="kpi-value">${{ formatNumber(financeData.accountsPayable) }}</p>
                  <p class="kpi-trend">Average age: {{ financeData.apAvgAge }} days</p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #e0e7ff; color: #6366f1;">
                  <ChartBarIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Gross Profit Margin</p>
                  <p class="kpi-value">{{ financeData.grossProfitMargin }}%</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ financeData.marginGrowth }}% vs previous period
                  </p>
                </div>
              </div>
            </div>
            
            <!-- Charts -->
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Cash Flow Analysis</h3>
              </div>
              <div class="chart-body">
                <div class="chart-placeholder">
                  <ChartBarIcon class="placeholder-icon" />
                  <p class="placeholder-text">Line Chart: Cash Inflow vs Outflow Over Time</p>
                  <p class="placeholder-note">Shows cash receipts, payments, and net cash flow trends</p>
                </div>
              </div>
            </div>
            
            <div class="charts-section">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Accounts Receivable Aging</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: AR Aging Buckets</p>
                    <p class="placeholder-note">0-30 days, 31-60 days, 61-90 days, 90+ days</p>
                  </div>
                </div>
              </div>
              
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Accounts Payable Aging</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: AP Aging Buckets</p>
                    <p class="placeholder-note">0-30 days, 31-60 days, 61-90 days, 90+ days</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="charts-section">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Profitability by Product</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: Gross Margin % by Product</p>
                    <p class="placeholder-note">Shows which products are most profitable</p>
                  </div>
                </div>
              </div>
              
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Profitability by Customer</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: Gross Margin % by Customer</p>
                    <p class="placeholder-note">Shows customer profitability analysis</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Expense Analysis</h3>
              </div>
              <div class="chart-body">
                <div class="chart-placeholder">
                  <ChartPieIcon class="placeholder-icon" />
                  <p class="placeholder-text">Pie Chart: Expense Breakdown by Category</p>
                  <p class="placeholder-note">COGS, Operating Expenses, Overhead, etc.</p>
                </div>
              </div>
            </div>
            
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Budget vs Actual</h3>
              </div>
              <div class="chart-body">
                <div class="chart-placeholder">
                  <ChartBarIcon class="placeholder-icon" />
                  <p class="placeholder-text">Multi-Bar Chart: Budget vs Actual by Category</p>
                  <p class="placeholder-note">Compares budgeted vs actual spending across categories</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Operations Analytics Tab -->
        <div v-if="activeTab === 'operations'" class="tab-panel">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Loading operations analytics...</p>
          </div>
          
          <div v-else>
            <!-- KPI Cards -->
            <div class="kpi-grid">
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #dbeafe; color: #3b82f6;">
                  <CogIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Production Efficiency</p>
                  <p class="kpi-value">{{ operationsData.productionEfficiency }}%</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ operationsData.efficiencyGrowth }}% vs previous period
                  </p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #dcfce7; color: #16a34a;">
                  <TruckIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Order Fulfillment Rate</p>
                  <p class="kpi-value">{{ operationsData.fulfillmentRate }}%</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ operationsData.fulfillmentGrowth }}% vs previous period
                  </p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #fef3c7; color: #d97706;">
                  <ClockIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Avg Lead Time</p>
                  <p class="kpi-value">{{ operationsData.avgLeadTime }} days</p>
                  <p class="kpi-trend negative">
                    <ArrowTrendingDownIcon class="trend-icon" />
                    {{ operationsData.leadTimeChange }}% vs previous period
                  </p>
                </div>
              </div>
              
              <div class="kpi-card">
                <div class="kpi-icon" style="background: #e0e7ff; color: #6366f1;">
                  <CheckCircleIcon class="icon" />
                </div>
                <div class="kpi-content">
                  <p class="kpi-label">Quality Score</p>
                  <p class="kpi-value">{{ operationsData.qualityScore }}%</p>
                  <p class="kpi-trend positive">
                    <ArrowTrendingUpIcon class="trend-icon" />
                    {{ operationsData.qualityGrowth }}% vs previous period
                  </p>
                </div>
              </div>
            </div>
            
            <!-- Charts -->
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Production Output Trend</h3>
              </div>
              <div class="chart-body">
                <div class="chart-placeholder">
                  <ChartBarIcon class="placeholder-icon" />
                  <p class="placeholder-text">Line Chart: Production Volume Over Time</p>
                  <p class="placeholder-note">Shows daily/weekly production output with target lines</p>
                </div>
              </div>
            </div>
            
            <div class="charts-section">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Order Fulfillment Metrics</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: On-Time vs Late Deliveries</p>
                    <p class="placeholder-note">Shows fulfillment performance over time</p>
                  </div>
                </div>
              </div>
              
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Supplier Performance</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: Supplier On-Time Delivery Rate</p>
                    <p class="placeholder-note">Compares supplier reliability</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="charts-section">
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Quality Metrics</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Line Chart: Defect Rate Over Time</p>
                    <p class="placeholder-note">Shows quality trends and improvement</p>
                  </div>
                </div>
              </div>
              
              <div class="chart-card">
                <div class="chart-header">
                  <h3>Resource Utilization</h3>
                </div>
                <div class="chart-body">
                  <div class="chart-placeholder">
                    <ChartBarIcon class="placeholder-icon" />
                    <p class="placeholder-text">Bar Chart: Equipment/Labor Utilization</p>
                    <p class="placeholder-note">Shows how efficiently resources are used</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Lead Time Analysis</h3>
              </div>
              <div class="chart-body">
                <div class="chart-placeholder">
                  <ChartBarIcon class="placeholder-icon" />
                  <p class="placeholder-text">Box Plot: Lead Time Distribution by Product Category</p>
                  <p class="placeholder-note">Shows min, max, median lead times across categories</p>
                </div>
              </div>
            </div>
            
            <!-- Operations Summary Table -->
            <div class="chart-card full-width">
              <div class="chart-header">
                <h3>Operations Summary by Work Center</h3>
              </div>
              <div class="chart-body">
                <div class="table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Work Center</th>
                        <th>Output (Units)</th>
                        <th>Efficiency %</th>
                        <th>Quality %</th>
                        <th>Downtime (hrs)</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="i in 5" :key="i">
                        <td>Work Center {{ i }}</td>
                        <td>{{ formatNumber(1000 + i * 200) }}</td>
                        <td><span :class="['badge', i % 2 === 0 ? 'badge-success' : 'badge-warning']">{{ 85 + i * 2 }}%</span></td>
                        <td>{{ 95 + i }}%</td>
                        <td>{{ i * 2 }}</td>
                        <td><span class="badge badge-success">Operational</span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { 
  ArrowPathIcon,
  ArrowDownTrayIcon,
  ClockIcon,
  CurrencyDollarIcon,
  ShoppingCartIcon,
  ChartBarIcon,
  ChartPieIcon,
  FunnelIcon,
  CubeIcon,
  ExclamationTriangleIcon,
  BanknotesIcon,
  DocumentTextIcon,
  ReceiptPercentIcon,
  CogIcon,
  TruckIcon,
  CheckCircleIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  BuildingStorefrontIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'
import reportingService from '../../../services/reportingService'

const loading = ref(false)
const activeTab = ref('sales')

const filters = ref({
  dateRange: 'month',
  startDate: '',
  endDate: '',
  comparison: ''
})

const tabs = [
  { id: 'sales', label: 'Sales', icon: CurrencyDollarIcon },
  { id: 'inventory', label: 'Inventory', icon: CubeIcon },
  { id: 'finance', label: 'Finance', icon: BanknotesIcon },
  { id: 'operations', label: 'Operations', icon: CogIcon }
]

const salesData = ref({
  totalRevenue: 1234567,
  revenueGrowth: 15.3,
  totalOrders: 4523,
  ordersGrowth: 12.8,
  avgOrderValue: 273,
  aovGrowth: -2.1,
  conversionRate: 3.2,
  conversionGrowth: 8.5
})

const inventoryData = ref({
  totalValue: 5678901,
  valueGrowth: 5.2,
  turnoverRate: 4.5,
  turnoverGrowth: 10.3,
  lowStockCount: 23,
  slowMovingCount: 47
})

const financeData = ref({
  netCashFlow: 456789,
  cashFlowGrowth: 22.4,
  accountsReceivable: 234567,
  arAvgAge: 32,
  accountsPayable: 187654,
  apAvgAge: 28,
  grossProfitMargin: 42.5,
  marginGrowth: 3.2
})

const operationsData = ref({
  productionEfficiency: 87.5,
  efficiencyGrowth: 4.2,
  fulfillmentRate: 94.3,
  fulfillmentGrowth: 2.1,
  avgLeadTime: 5.2,
  leadTimeChange: 8.3,
  qualityScore: 96.8,
  qualityGrowth: 1.5
})

onMounted(() => {
  fetchAnalytics()
})

const fetchAnalytics = async () => {
  loading.value = true
  try {
    const params = buildFilterParams()
    
    if (activeTab.value === 'sales') {
      await reportingService.getSalesAnalytics(params)
    } else if (activeTab.value === 'inventory') {
      await reportingService.getInventoryAnalytics(params)
    } else {
      await reportingService.getAnalytics({ ...params, type: activeTab.value })
    }
  } catch (err) {
    console.error('Failed to fetch analytics:', err)
  } finally {
    loading.value = false
  }
}

const buildFilterParams = () => {
  const params = {
    date_range: filters.value.dateRange
  }
  
  if (filters.value.dateRange === 'custom') {
    params.start_date = filters.value.startDate
    params.end_date = filters.value.endDate
  }
  
  if (filters.value.comparison) {
    params.comparison = filters.value.comparison
  }
  
  return params
}

const handleDateRangeChange = () => {
  if (filters.value.dateRange !== 'custom') {
    applyFilters()
  }
}

const applyFilters = () => {
  fetchAnalytics()
}

const refreshAnalytics = () => {
  fetchAnalytics()
}

const scheduleReport = () => {
  alert('Schedule report functionality - configure email frequency and recipients')
}

const exportData = () => {
  alert('Export analytics data to Excel/CSV')
}

const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num?.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') || '0'
}

const getRandomDate = () => {
  const dates = [
    '2024-01-15',
    '2024-02-20',
    '2024-03-10',
    '2024-04-05',
    '2024-05-12'
  ]
  return dates[Math.floor(Math.random() * dates.length)]
}
</script>

<style scoped>
.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 24px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.header-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

.btn-filter {
  align-self: flex-end;
}

.icon {
  width: 20px;
  height: 20px;
}

.icon-small {
  width: 16px;
  height: 16px;
}

.filters-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  margin-bottom: 24px;
}

.filters-row {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.filter-select,
.filter-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: white;
}

.filter-select:focus,
.filter-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.tabs-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.tabs-header {
  display: flex;
  border-bottom: 2px solid #e5e7eb;
  overflow-x: auto;
}

.tab-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 24px;
  border: none;
  background: none;
  color: #6b7280;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
}

.tab-button:hover {
  color: #374151;
  background: #f9fafb;
}

.tab-button.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.tab-icon {
  width: 18px;
  height: 18px;
}

.tabs-content {
  padding: 24px;
}

.tab-panel {
  animation: fadeIn 0.2s;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 3px solid #f3f4f6;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.kpi-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  gap: 16px;
}

.kpi-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.kpi-content {
  flex: 1;
}

.kpi-label {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 4px;
}

.kpi-value {
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 4px;
}

.kpi-trend {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #6b7280;
}

.kpi-trend.positive {
  color: #16a34a;
}

.kpi-trend.negative {
  color: #dc2626;
}

.trend-icon {
  width: 14px;
  height: 14px;
}

.charts-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.chart-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}

.chart-card.full-width {
  margin-bottom: 20px;
}

.chart-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chart-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.chart-actions {
  display: flex;
  gap: 8px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  background: #f3f4f6;
  color: #6b7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #e5e7eb;
  color: #374151;
}

.chart-body {
  padding: 24px;
}

.chart-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 250px;
  background: #f9fafb;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  padding: 24px;
}

.placeholder-icon {
  width: 48px;
  height: 48px;
  color: #9ca3af;
  margin-bottom: 12px;
}

.placeholder-text {
  font-size: 14px;
  font-weight: 500;
  color: #6b7280;
  margin-bottom: 4px;
  text-align: center;
}

.placeholder-note {
  font-size: 12px;
  color: #9ca3af;
  text-align: center;
  margin-top: 4px;
}

.funnel-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 20px;
}

.funnel-stage {
  margin: 0 auto;
}

.funnel-bar {
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  border-radius: 8px;
  color: white;
  font-weight: 500;
}

.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f9fafb;
}

.data-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
}

.data-table td {
  padding: 12px 16px;
  font-size: 14px;
  color: #374151;
  border-bottom: 1px solid #f3f4f6;
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.badge {
  display: inline-block;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 12px;
}

.badge-success {
  background: #dcfce7;
  color: #16a34a;
}

.badge-warning {
  background: #fef3c7;
  color: #d97706;
}

.badge-danger {
  background: #fee2e2;
  color: #dc2626;
}

.trend-badge {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.trend-badge.positive {
  background: #dcfce7;
  color: #16a34a;
}

.trend-badge.negative {
  background: #fee2e2;
  color: #dc2626;
}

@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  
  .charts-section {
    grid-template-columns: 1fr;
  }
  
  .filters-row {
    flex-direction: column;
  }
  
  .filter-group {
    width: 100%;
  }
}
</style>
