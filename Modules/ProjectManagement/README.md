# ProjectManagement Module

Enterprise project management module for the ERP/CRM platform, providing full lifecycle management of projects, tasks, milestones, and time tracking.

## Features

- **Projects** — Create and manage projects with budget tracking, status workflow, and customer association.
- **Tasks** — Assign tasks to projects and team members with priority and status management.
- **Milestones** — Define and track project milestones with achievement status.
- **Time Tracking** — Log time entries against projects and tasks; project `spent` is updated automatically using BCMath.

## Architecture

Follows Clean Architecture with four layers:

| Layer | Location |
|-------|----------|
| Domain | `Domain/` — Entities, Enums, Events, Contracts |
| Application | `Application/UseCases/` |
| Infrastructure | `Infrastructure/Models/`, `Infrastructure/Repositories/`, `Infrastructure/Migrations/` |
| Presentation | `Presentation/Controllers/`, `Presentation/Requests/` |

## API Endpoints

All endpoints require `auth:sanctum` middleware.

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/api/v1/pm/projects` | List / create projects |
| GET/PUT/DELETE | `/api/v1/pm/projects/{id}` | Show / update / delete project |
| GET/POST | `/api/v1/pm/tasks` | List / create tasks |
| GET/PUT/DELETE | `/api/v1/pm/tasks/{id}` | Show / update / delete task |
| POST | `/api/v1/pm/tasks/{id}/complete` | Mark task as done |
| GET/POST | `/api/v1/pm/time-entries` | List / log time entries |
| GET/DELETE | `/api/v1/pm/time-entries/{id}` | Show / delete time entry |

## Multi-Tenancy

All tables include `tenant_id` and models use the `HasTenantScope` trait to enforce tenant isolation on every query.

## Financial Integrity

Budget and spent values use `DECIMAL(18,8)` with BCMath arithmetic (scale 8). Hours use `DECIMAL(10,2)` with BCMath (scale 2).
