# Vehicle service line action icon buttons

Date: 2026-07-18

## Problem

The vehicle service job-line table still used text actions for editable rows. That took more horizontal space and felt heavier than the surrounding UI, especially after the combo-pack grouping improvements.

## Change

- replaced job-line `Edit line` and `Remove line` text actions with compact icon buttons;
- kept combo child rows non-actionable, so included items still show no actions at all;
- preserved the same edit and remove behavior while improving visual consistency and table density.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend vehicle service job-line action controls.
