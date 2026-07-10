# Vehicle Rental decimal response integrity

Date: 2026-07-11

## Context

The Vehicle Rental base API resource formatted persisted DECIMAL values by converting them to binary floating point before returning six-decimal strings. That response boundary could lose precision even when storage and calculation services retained exact decimal values.

## Changes

- Vehicle Rental resources now normalize decimal responses through the existing Core `DecimalMath` service.
- The float-based `number_format` conversion was removed.
- A focused contract test protects the resource, allocation, and custody odometer paths from reintroducing binary float conversion.

## Scope and verification

This is a Vehicle Rental presentation-boundary correction using the existing Core decimal source of truth. No schema, financial calculation, or unrelated module behavior changed. The changed PHP source is syntactically valid; the full project suite must be rerun in the project runtime after merge.
