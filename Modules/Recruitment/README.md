# Recruitment Module

Applicant Tracking System (ATS) for managing job positions, candidate applications, interview stages, and hire/reject workflow.

## Features

- **Job Positions** — define open roles with employment type, vacancies, department, and location
- **Job Applications** — candidates apply to open positions; track source, resume, and cover letter
- **Hire/Reject Workflow** — reviewers can hire or reject applicants, dispatching domain events
- Tenant-isolated; all queries are scoped via `HasTenantScope`
- BCMath not required (no financial calculations in this module)

## Status Lifecycles

### Job Position
`open` → `on_hold` → `closed`

### Job Application
`new` → `in_review` → `interview` → `offer` → `hired` | `rejected`

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/recruitment/positions` | List / create job positions |
| GET/PUT/DELETE | `api/v1/recruitment/positions/{id}` | Read / update / soft-delete position |
| GET/POST | `api/v1/recruitment/applications` | List / create applications |
| GET/DELETE | `api/v1/recruitment/applications/{id}` | Read / soft-delete application |
| POST | `api/v1/recruitment/applications/{id}/hire` | Hire an applicant |
| POST | `api/v1/recruitment/applications/{id}/reject` | Reject an applicant |

## Domain Events

| Event | Trigger |
|-------|---------|
| `JobApplicationReceived` | New application submitted to an open position |
| `ApplicantHired` | Applicant status set to `hired` |
| `ApplicantRejected` | Applicant status set to `rejected` |

## Dependencies

- Tenant, User, HR, Notification, Audit
