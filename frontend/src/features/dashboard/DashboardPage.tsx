/**
 * DashboardPage — landing page after authentication.
 *
 * Displays a summary of the ERP modules available to the current user.
 * Detailed KPI widgets will be added per module as each feature area matures.
 */
export default function DashboardPage() {
  return (
    <section aria-label="Dashboard">
      <h1>Dashboard</h1>
      <p>Welcome to KV Enterprise ERP/CRM. Select a module from the sidebar to get started.</p>

      <div className="module-grid">
        {MODULE_CARDS.map((card) => (
          <a key={card.href} href={card.href} className="module-card">
            <h2>{card.title}</h2>
            <p>{card.description}</p>
          </a>
        ))}
      </div>
    </section>
  )
}

const MODULE_CARDS = [
  { title: 'Inventory', href: '/inventory', description: 'Ledger-driven stock management, reservations, and transfers.' },
  { title: 'Sales', href: '/sales', description: 'Quotation → Order → Delivery → Invoice → Payment.' },
  { title: 'POS', href: '/pos', description: 'Offline-first point-of-sale terminal with sync reconciliation.' },
  { title: 'Procurement', href: '/procurement', description: 'Purchase requests, RFQs, three-way matching.' },
  { title: 'CRM', href: '/crm', description: 'Lead → Opportunity → Proposal → Closed Won/Lost.' },
  { title: 'Warehouse', href: '/warehouse', description: 'Bin-level tracking, putaway rules, picking orders.' },
  { title: 'Accounting', href: '/accounting', description: 'Double-entry bookkeeping, journal entries, P&L.' },
  { title: 'Reporting', href: '/reporting', description: 'Financial statements, inventory reports, custom exports.' },
  { title: 'Product Catalog', href: '/product', description: 'Product types, SKU management, UOM conversions, and variant support.' },
  { title: 'Organisation', href: '/organisation', description: 'Tenant → Organisation → Branch → Location → Department hierarchy.' },
  { title: 'Metadata', href: '/metadata', description: 'Custom fields, dynamic forms, and runtime-configurable validation rules.' },
  { title: 'Workflow', href: '/workflow', description: 'State machine flows, approval chains, escalation rules, and SLA enforcement.' },
  { title: 'Notification', href: '/notification', description: 'Multi-channel notification engine: email, SMS, push, and in-app.' },
  { title: 'Integration', href: '/integration', description: 'Webhooks, event publishing, and third-party ERP/CRM connectors.' },
  { title: 'Plugin', href: '/plugin', description: 'Marketplace-ready plugin registry with dependency validation and tenant enablement.' },
  { title: 'Tenancy', href: '/tenancy', description: 'Platform-level multi-tenant registry with row-level isolation and pharma compliance mode.' },
  { title: 'Pricing', href: '/pricing', description: 'Rule-based pricing engine: price lists, discount rules, and BCMath price calculation.' },
]
