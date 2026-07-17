# Audit document authority

Date: 2026-07-16

## Problem

Historical AutoERP audits and legacy video audits remained useful evidence, but they did not consistently distinguish current defects, resolved findings, verification gaps, historical evidence, and unapproved business decisions.

This could cause a resolved defect or a legacy-system weakness to be treated as a current production-code problem.

## Correction

Added `docs/AUDIT_AUTHORITY.md` as the single interpretation guide for audit material.

It defines:

- the authoritative precedence of current code, same-commit tests, change records, specifications, historical audits, and legacy video evidence;
- required finding status labels;
- how Vehicle Rental video evidence should be used;
- precise verification language;
- categories that must remain release or decision gates instead of speculative code changes.

Historical reports remain append-only and were not edited or deleted.

## Relationships

No production code, schema, or relationship changed.
