# Supplier list search simplification

Date: 2026-08-28

## Purpose

Simplify the Supplier List so users can search quickly without unnecessary filter controls or category information in the primary table.

## Changes

- Retained the main supplier Search input.
- Removed the Supplier type, Status, Category, Credit allowed, Sort, and Direction controls from the Supplier List UI.
- Removed the Categories column from the Supplier List table.
- Removed the hidden controls' local state and API query parameters from the page instead of leaving dead frontend logic.
- Kept the supplier API filtering capabilities available for lookup components and other API consumers.
- Kept the newly added Total Due column unchanged.

## Verification

- Frontend TypeScript checking passed.
- Production Vite build passed.
- Git whitespace validation passed.
