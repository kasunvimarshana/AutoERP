# Supplier form identity simplification

Date: 2026-08-28

## Purpose

Simplify supplier identity entry by removing duplicate or system-owned fields while preserving safe, editable supplier codes.

## Changes

- Removed Supplier Number from the create and edit form; the backend continues to generate it automatically.
- Removed the separate Legal Name input and relabelled the canonical Name input as Legal Name.
- Kept the internal legal-name value synchronized with the canonical name so invoice and document snapshots receive the expected legal identity.
- Preserved existing suppliers' stored legal name when first opening the revised edit form.
- Made Code optional during supplier creation. A blank Code is generated on save, while a user-entered Code remains unchanged.
- Added a dedicated tenant-scoped supplier-code sequence and collision recovery for both generated codes and supplier numbers, including soft-deleted suppliers and manually entered references.
- Updated validation messages and frontend guidance to reflect the revised identity fields.

## Verification

- Supplier API and domain tests passed: 22 tests, 202 assertions.
- PHP syntax checks passed for the changed supplier services and validator.
- Frontend TypeScript checking passed.
- Production Vite build passed.
- Git whitespace validation passed.
