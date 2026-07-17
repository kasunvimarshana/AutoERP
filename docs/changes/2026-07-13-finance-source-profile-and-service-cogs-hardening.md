# Finance Source, Profile, and Vehicle Service COGS Hardening

**Date:** 2026-07-13  
**Branch:** `worktree-0.0.8`

## Context

A post-green re-audit of the authoritative branch identified four deterministic ownership and integrity gaps:

1. the manual Finance journal API and form allowed a user to claim business-module source identity;
2. Finance exact-once source identity included a descriptive module label, allowing the same business source to produce a different idempotency key through an alias change;
3. organization Finance configuration did not clearly expose tenant fallback profiles and posting-profile updates could silently overwrite concurrent edits;
4. Vehicle Service parts issues reduced valued Inventory without posting the corresponding cost of goods sold journal.

A related Vehicle Finance relationship gap was also confirmed: terminal installment invoices did not release the owning installment's invoice link. The broader principal, interest, fee, initial-deposit, asset-recognition, and finance-liability GL policy remains intentionally unimplemented because the required accounting policy is not defined by the current product contract.

## Changes

### Manual journal source ownership

- Manual journal requests now prohibit header and line-level business source identity fields.
- The manual journal form no longer exposes source module, type, identifier, number, or date inputs.
- Business source identity remains available only to source-module integrations through `FinancePostingInterface`.

### Canonical posting source identity

- Finance source keys now use tenant, organization, source type, and source identifier.
- The descriptive source module remains immutable journal evidence but no longer changes exact-once identity.
- A fail-closed upgrade migration recalculates existing source keys and aborts when historical rows collapse to the same canonical identity.

### Posting profile scope and concurrency

- Organization contexts now receive both exact organization profiles and inherited tenant-default profiles.
- Profile resources expose stored scope and `row_version` explicitly.
- Inherited tenant profiles and account assignments are visible but read-only in an organization context.
- Posting-profile updates require `expected_version`, lock the profile, and increment the version inside the Finance owner service.
- Effective-dated revisions may reuse a semantic line key; duplicate effective-from dates and overlapping active periods remain rejected by the owner service.
- A deployed-schema upgrade migration adds `row_version` to Finance posting profiles.

### Vehicle Service parts COGS

- Vehicle Service parts issue now posts one Finance journal per authoritative Inventory movement.
- The movement's persisted `total_cost` is the accounting amount.
- The semantic posting is debit Cost of Goods Sold and credit Inventory through the canonical `inventory_issue` profile.
- Inventory movement creation, Finance posting, job-line linkage, status update, and job-version update remain in one transaction.
- No account identifiers or account codes were added to the Vehicle Service module.

### Vehicle Finance invoice relationship

- Vehicle Rental now owns restoration of a finance installment's invoice link when its Invoice becomes cancelled, void, or reversed.
- The installment is locked, the link is cleared only when it points to the terminal Invoice, and its row version is incremented.
- Vehicle Finance payable creation is restricted to Draft status until a governed Vehicle Finance GL policy exists.

## Relationship decisions

- **Removed:** manual journal to arbitrary business-source relationship. This relationship was user-authored evidence and violated source-module ownership.
- **Canonicalized:** Finance journal to business-source exact-once identity. Descriptive module aliases no longer create distinct relationships.
- **Clarified:** organization posting profile to tenant-default profile inheritance. Inherited records remain owned by tenant scope and cannot be mutated from an organization context.
- **Added:** Vehicle Service Inventory movement to its COGS journal, one-to-one by canonical source identity. This uses the movement as the valuation source and avoids job-level aggregation conflicts across partial issues.
- **Restored:** Vehicle Finance installment to Invoice link lifecycle. Terminal invoices release the owner link without changing installment due-state semantics.

## Branch integration decision

`worktree-0.0.16` was already fully absorbed by the authoritative branch. The remaining commits on `kushan/frontend_base` were not merged blindly. Their lookup approach downloads every page of employee and vehicle-reference datasets into browser memory on form load. That design introduces unbounded API and memory cost as tenant data grows and would also reintroduce conflicts with newer target-branch fixes. The existing bounded, query-cached lookup contract remains authoritative.

## Verification status

The changes were reviewed from the committed authoritative branch after each write and include targeted boundary and workflow tests. This connector environment cannot run PHP, MySQL, TypeScript, ESLint, Vite, or Vitest. A local migration rehearsal and the targeted/full verification commands must pass before deployment.

## Policy-dependent work not guessed

Vehicle Finance GL posting remains blocked pending an explicit accounting policy covering:

- whether agreement activation recognizes a vehicle asset or an existing acquisition document owns that recognition;
- how the initial deposit is represented and linked to Payment;
- the finance-liability account and principal reduction lifecycle;
- interest and fee expense recognition timing;
- tax treatment and reversal behavior.

Implementing those entries without the policy could double-recognize the vehicle asset or expense principal, so no speculative compatibility mapping was introduced.
