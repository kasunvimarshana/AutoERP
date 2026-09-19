# Current system technical specification

Date: 2026-09-18

## Summary

- Added `docs/AUTOERP_CURRENT_SYSTEM_TECHNICAL_SPECIFICATION.md` as a durable current-state reference for AutoERP.
- Consolidated the implemented feature catalogue, exact framework/dependency versions, modular-monolith architecture, module ownership, design patterns, consistency model, API/frontend conventions, security controls, deployment requirements, known boundaries, and source map.
- Reconciled the existing system context with the current registered modules, frontend routes, recent verified September feature records, dependency lockfiles, and the inspected local runtime.
- Explicitly separated code-enforced security controls from environment/operations requirements and documented current local deployment gaps without claiming production certification.
- Recorded branch `dashboard-phase2` and commit `eea8064c2ae0` as the snapshot boundary.

## Why

The project needed one code-referenced document that can be used later for onboarding, technical review, planning, audit preparation, and future development without reconstructing the system from many modules and change records.

## Verification

- Confirmed 28 explicitly registered backend modules in `bootstrap/providers.php`.
- Verified backend and frontend dependency versions against Composer/NPM lockfiles and the installed dependency tree.
- Reviewed the route trees, authentication/security implementation, tenant-scoping base model/request, idempotency service, audit sanitization, architecture index, existing AI system context, and latest relevant change records.
- Ran `php artisan about` to capture the current local runtime separately from intended production settings.
- Documentation-only change; application runtime behavior was not modified.
