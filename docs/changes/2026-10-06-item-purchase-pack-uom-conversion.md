# Support purchase pack to base UOM conversion

## Request

Allow stock purchased in count-based packs such as pieces to be received into volume-based inventory and consumed by a vehicle service combo in liters.

## Changes

- Allow an item-specific purchase UOM to convert across generic UOM categories; other item unit roles continue to require compatible UOM categories.
- Allow purchase prices to use a cross-category UOM only when that UOM is configured as an active Purchase item unit.
- Clarify that the conversion factor means base units per one selected UOM, with a purchase-pack example in the item unit form.
- The inventory and purchase modules already apply this item-specific factor to convert received quantities and unit costs to the item Base UOM.

## Verification

- TypeScript typecheck passed.
- PHP syntax checks passed for changed PHP files.
- `git diff --check` passed.
- Automated tests were not run.
