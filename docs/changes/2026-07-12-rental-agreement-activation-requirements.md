# Vehicle Rental agreement activation requirements

Date: 2026-07-12

## Problem

Vehicle Rental tests described two contradictory agreement-clause rules: one contract label implied printable clauses were required for activation while the supported business rule allows agreements to activate with no printable clauses. The actual activation invariant is execution context, not clause presence.

## Root cause

The printable-clause prerequisite was removed from the owner service, but an older static contract description continued to use the previous wording. Execution date and legal context were also covered together inside a broad feature test rather than being independently proven.

## Correction

- Updated the Vehicle Rental integrity contract name and assertions to describe the current rule precisely.
- Kept printable agreement clauses optional and preserved immutable snapshot capture when clauses are present or absent.
- Added independent behavioral tests proving that a missing execution date blocks activation.
- Added independent behavioral tests proving that a missing legal context blocks activation.
- Verified that rejected activation leaves the agreement in Draft, preserves its row version, and does not create a document snapshot.

## Scope

This is a test-contract clarification only. It does not change agreement lifecycle logic, persistence, rate versions, clauses, snapshots, schema, API contracts, or frontend behavior.

## Verification

- PHP syntax validation passed for the updated unit contract test.
- PHP syntax validation passed for the new feature test.
- Re-fetched both files from `worktree-0.0.8` and confirmed their complete final contents.
- Full Laravel execution remains required in the normal project environment because the connector environment does not contain the repository dependencies.
