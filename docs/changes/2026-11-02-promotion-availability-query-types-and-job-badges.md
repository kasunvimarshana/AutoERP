# Promotion availability query types and job badges

The Service Job promotion strip returned HTTP 500 after selecting a customer and vehicle. The Laravel log identified a `TypeError`: the validated GET query values for `customer_id` and `vehicle_id` remained strings, but `PromotionRuntimeService::available()` requires integers. The Promotion controller now casts validated numeric values at typed service boundaries. The same correction covers card filters, vehicle transfer, card status, and promotion choice version values, which had the same risk.

The job promotion strip now shows compact status badges for a direct card benefit's discount, remaining uses, and weekday availability, and for a repeat offer's paid progress, remaining count, and earned free uses. Existing add-line actions remain in place.

Verification: the logged exception and controller call were matched. A focused HTTP regression test passed with numeric query-string IDs (`1 test, 3 assertions`); PHP syntax, changed TSX parsing, and `git diff --check` passed. No database schema or manual discount flow changed.
