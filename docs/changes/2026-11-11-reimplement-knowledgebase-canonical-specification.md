# Knowledge Base: Canonical Specification Reimplementation

**Date:** 2026-11-11  
**Module:** `Documentation` (`docs/knowledgebase.md`)  
**Scope:** Reimplemented `docs/knowledgebase.md` as the authoritative, self-contained domain knowledge base and canonical architecture specification. Integrates the overarching AutoERP modular architecture rules with deep domain specifications for Vehicle Rental, reconciling the TACGL legacy corpus, real-world scanned document proofs, closed-loop financial calculations, and the complete 8-report production suite.

---

## 1. Summary of Changes & Enhancements

### A. Architectural & Domain Synthesis
- **Modular Monolith & Tenancy Foundations:** Fully documented the shared-schema multi-tenancy model, explicit organization unit scoping, bounded data access, optimistic concurrency (`row_version` / `expected_version`), and strict module boundaries across Core, Vehicle Rental, Invoice, Payment, Finance, HR, and Vehicle Service.
- **Closed-Loop Reconciliation Proofs:** Incorporated the real-world mathematical evidence from scanned production attachments (`prndeblogsheet2.pdf`, `prncre1vehstat_b.pdf`, `prncre1vehstat_c.pdf`, `pmglled.frx`), detailing the exact derivation of overtime hours, pass outlays, lessor gross payables, repair deductions (`CDN`), cheque settlements (`PRB`), and closing balances.
- **Visual Operational Tracking:** Specified the 31-day running chart matrix mechanics, including dynamic vehicle replacement annotations (`For Repl. <vehicle_no>`) and yellow row highlights matching physical production evidence (`media_1794379855450_9d7d2914.jpg`).
- **Full Report Suite Specifications:** Documented the layout orientation, formulas, and legacy Visual FoxPro `.FRX` mappings for all 8 official reports, including Statement Detail I (A4 Portrait), Statement Detail II (A4 Landscape 18-column operational matrix), Running Chart 31-day matrix (A4 Landscape), and the Withholding Tax liability ledger (`:3305-000 WHT`).
- **UI/UX Ergonomics & Clean Design Standards:** Articulated the non-negotiable rules for zero raw database ID exposure, controlled lookups, progressive disclosure via `<details>` accordions, and timezone-aware civil business calendar integration.

---

## 2. Verification & Validation

- **File Integrity:** Replaced outdated contents with a structured, comprehensive, and clean specification.
- **Test Suite Verification:**
  - Backend Tests: `php artisan test app/Modules/VehicleRental/Tests` &rarr; 234 passed (2,007 assertions).
  - Frontend Tests: `npx vitest run resources/js/modules/vehicle-rental --fileParallelism=false` &rarr; 29 files passed (138 tests).
  - TypeScript Typecheck: `npm run typecheck` &rarr; 0 errors.
  - ESLint: `npm run lint` &rarr; 0 warnings, 0 errors.
  - Production Asset Build: `npm run build` &rarr; built in 8.05s.
