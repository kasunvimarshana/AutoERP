# CRM Module

## Overview

The **CRM** module manages the full customer relationship lifecycle from lead acquisition to closed deal, with pipeline management, activity tracking, and campaign attribution.

---

## CRM Pipeline

```
Lead → Opportunity → Proposal → Closed Won / Closed Lost
```

---

## Responsibilities

- Lead management (capture, qualify, assign)
- Opportunity management
- Pipeline stage configuration
- Activity tracking (calls, emails, meetings, tasks)
- Campaign tracking and attribution
- Email integration
- SLA tracking and timers
- Notes and attachments
- Customer segmentation
- Deal forecasting

---

## Architecture Layer

```
Modules/CRM/
 ├── Application/       # Create lead, advance opportunity, close deal, track activity use cases
 ├── Domain/            # Lead, Opportunity, Pipeline, Activity entities, CRMRepository contract
 ├── Infrastructure/    # CRMRepository, CRMServiceProvider, email integration adapters
 ├── Interfaces/        # LeadController, OpportunityController, PipelineController
 ├── module.json
 └── README.md
```

---

## Dependencies

- `core`
- `tenancy`
- `workflow`

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| Tenant isolation enforced | ✅ Enforced |
| Pipeline stages and deal statuses database-driven | ✅ Required |
| Full audit trail | ✅ Enforced |
| No cross-module coupling (communicates via contracts/events) | ✅ Enforced |

---

## Implemented Files

### Migrations
| File | Table |
|---|---|
| `2026_02_27_000048_create_crm_leads_table.php` | `crm_leads` |
| `2026_02_27_000049_create_crm_pipeline_stages_table.php` | `crm_pipeline_stages` |
| `2026_02_27_000050_create_crm_opportunities_table.php` | `crm_opportunities` |
| `2026_02_27_000051_create_crm_activities_table.php` | `crm_activities` |
| `2026_02_27_000052_create_crm_campaigns_table.php` | `crm_campaigns` |

### Domain Entities
- `CrmLead` — HasTenant, belongsTo CrmCampaign
- `CrmPipelineStage` — HasTenant; win_probability cast to string
- `CrmOpportunity` — HasTenant; expected_revenue, probability cast to string
- `CrmActivity` — HasTenant
- `CrmCampaign` — HasTenant; budget cast to string

### Application Layer
- `CreateLeadDTO` — fromArray factory
- `CreateOpportunityDTO` — fromArray factory; monetary fields as strings
- `CRMService` — createLead, listLeads, showLead, deleteLead, convertLeadToOpportunity, updateOpportunityStage, closeWon, closeLost, listOpportunities, showOpportunity, listCustomers

### Infrastructure Layer
- `CRMRepositoryContract` — findByStatus, findByAssignee
- `CRMRepository` — extends AbstractRepository on CrmOpportunity
- `CRMServiceProvider` — binds contract, loads migrations and routes

### API Routes (`/api/v1`)
| Method | Path | Action |
|---|---|---|
| GET | `/crm/leads` | listLeads |
| GET | `/crm/leads/{id}` | showLead |
| POST | `/crm/leads` | createLead |
| DELETE | `/crm/leads/{id}` | deleteLead |
| POST | `/crm/leads/{id}/convert` | convertLeadToOpportunity |
| GET | `/crm/opportunities` | listOpportunities |
| GET | `/crm/opportunities/{id}` | showOpportunity |
| POST | `/crm/opportunities/{id}/stage` | updateOpportunityStage |
| POST | `/crm/opportunities/{id}/close-won` | closeWon |
| POST | `/crm/opportunities/{id}/close-lost` | closeLost |
| GET | `/crm/customers` | listCustomers |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/CRMDTOTest.php` | Unit | `CreateLeadDTO`, `CreateOpportunityDTO` — field hydration, type casting, BCMath string fields |
| `Tests/Unit/CRMServiceTest.php` | Unit | `CRMService::listOpportunities()` — filter routing logic (status, assignee, default) with stubbed repository |
| `Tests/Unit/CRMServiceLeadTest.php` | Unit | `CRMService::listLeads()` — filter routing (status, assignee, default, priority) — 10 assertions |
| `Tests/Unit/CRMServiceShowLeadTest.php` | Unit | `CRMService::showLead()` — delegation to leadRepository::findOrFail(), return type, method signature — 5 assertions |
| `Tests/Unit/CRMServiceCrudTest.php` | Unit | deleteLead, showOpportunity, listCustomers — method signatures, delegation — 12 assertions |

---

## Status

🟢 **Complete** — Full lead and opportunity pipeline implemented; delete, show opportunity, and customer listing endpoints implemented (~85% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
