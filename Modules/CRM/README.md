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

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
