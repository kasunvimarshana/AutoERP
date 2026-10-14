# Invoice A5 print preview diagnosis

## Request

Identify why compact A5 invoices do not appear at the intended size in Chrome print preview with an Epson LQ-310, where the available printer paper sizes appear to be A4 and Letter.

## Findings

- Invoice layout configuration supports both A4 and A5 and emits the configured size through the invoice print page's `@page` rule.
- The compact layout is styled at 148 mm by 210 mm for screen preview, but its print-media rule resets the sheet width and height constraints. When the browser/driver uses A4, the compact content therefore follows the selected A4 page area rather than retaining the compact sheet dimensions.
- The supplied screenshot shows Chrome's selected paper size as A4. This means the browser is previewing against A4 despite the compact A5 layout request; the page-size request and the printer's selected media size are not aligned.
- Epson's LQ-310 specification supports single sheets 100–257 mm wide and 100–364 mm long, which includes A5 dimensions. The model itself is not the size limitation; the available A4/Letter choices point to driver or paper-source configuration.
- A4 scaled to 40% is not geometrically A5. A5 is approximately 70.7% of A4 in each dimension. A 40% result may look acceptable for this particular invoice, but it is an additional shrink setting rather than a correct A4-to-A5 mapping.

## Recommendation

Configure an A5 (148 mm × 210 mm) user-defined paper size in the Epson LQ-310 Windows driver, choose the matching sheet source, and select that size in Chrome. Print at default scale. If the driver has no user-defined size option, confirm that the installed printer driver is the Epson LQ-310 driver rather than a generic Windows driver.

No application code was changed. The application already requests A5 through CSS; the observed preview is using A4 media, and the print stylesheet lets the selected media size determine the compact sheet width.

## References

- Epson LQ-310 User's Guide, printer paper specifications: https://support2.epson.net/manuals/english/sidm/lq_310/bps0136-01_ug/html/apspe_2.htm
- Epson guidance for creating user-defined paper sizes in Windows: https://epson.custhelp.com/app/answers/detail/a_id/51950/~/creating-custom-paper-sizes-in-windows
