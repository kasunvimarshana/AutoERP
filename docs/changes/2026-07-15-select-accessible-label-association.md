# Select accessible label association

Date: 2026-07-15

## Problem

The shared `Select` component wrapped the field label, the `<select>`, all option text, and the hint/error message inside one `<label>` element. Browsers rendered the control, but exact accessible-label queries could not isolate the visible label text from the nested option and hint content.

This caused the Vehicle Service create/edit form regression tests to report that `Supervisor commission` was missing even though the control was present in the rendered DOM.

## Root cause

The shared select markup used an implicit nested-control label and an explicit `htmlFor` association at the same time. The nested option and hint text became part of the label node's text content.

## Correction

`Select` now follows the same structure already used by the shared `Textarea` component:

- a neutral wrapper owns layout styling;
- a dedicated `<label htmlFor>` contains only the human-readable field label;
- the `<select>` keeps its stable generated or supplied ID;
- error and hint text remain connected through `aria-describedby`;
- validation, values, options, disabled state, forwarded refs, and styling remain unchanged.

The Vehicle Service tests were not weakened or rewritten because they correctly expect the visible label to identify the control.

## Scope and relationships

No database schema, API, model, business rule, module relationship, or Vehicle Service payload changed. This is a shared frontend accessibility correction in the component that owns the markup defect.

## Verification

Run:

```bash
npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx --reporter=dot --silent
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
php artisan test
composer test:mysql
```
