# Selling quantity precision

## Change

- Limited the Selling line quantity input to three decimal places while leaving other DecimalInput fields unchanged.

## Reason

Selling quantities should accept fractional values such as `1.5` without displaying or retaining unnecessary six-place precision.

## Notes

No backend or schema changes were needed; existing sale quantities and stock movements support three decimal places.
