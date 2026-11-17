# Make continuous service invoice print trace readable

**Date:** 2026-11-17  
**Module:** Invoice  
**Scope:** 8.5 x 5.5 inch Service Invoice footer.

The printed timestamp, user, and copy label appeared crowded against the footer divider and could be hard to read in the browser print view. Increased the footer's top and bottom cell padding and font size, gave the print-trace cell more width, and allowed long trace text to wrap instead of being clipped. Reduced the continuous layout's main-content minimum height slightly so the padded footer remains on the same page. The text still comes from the existing print context; no timestamp, user, or copy label is hardcoded.

Verified a one-line example with `Printed: 16 Nov 2026 10:55 AM | By: admin tenant | Original` visibly rendered below the divider. The PDF remains one 612 x 396 point page. The full Invoice print suite passed (12 tests, 120 assertions), including four-line continuous, A4, A5, and purchase layouts. Pint and `git diff --check` passed. No invoice data, schema, or relationships changed.
