# Vehicle Rental frontend entry

## Problem

Enabling the `vehicle-rental` tenant-plan module activated the backend entitlement and API routes, but the tenant workspace had no Vehicle Rental route registration, route-entitlement policy, navigation item, or React page. The module therefore remained invisible even for correctly entitled tenants and users.

## Root cause

The fresh Vehicle Rental foundation and Running Chart/calculation batches deliberately excluded React workflow pages. The frontend tenant-module catalogue was updated, but the feature-owned frontend entry boundary was never added.

## Change

- Added the Vehicle Rental frontend permission catalogue.
- Added feature-owned route-entitlement rules requiring the `vehicle-rental` module, an organization unit, and the matching view permission.
- Added one Vehicle Rental navigation module under Operations with Overview, Agreements, Vehicle Assignments, Running Charts, and Calculations links.
- Registered the owned `/vehicle-rental/*` React route without changing existing routes.
- Added a read workspace backed by the canonical Vehicle Rental APIs for agreements, assignments, running charts, and calculation snapshots.
- Added a focused frontend contract test for navigation and route-entitlement registration.

## Scope boundary

This change exposes the already-implemented operational foundation. It does not alter backend lifecycle rules, calculation formulas, database schema, Invoice, Payment, Tax, Finance, Reporting, or tenant-plan semantics. Mutation forms remain a separate workflow batch rather than being implemented as partial or misleading controls.
