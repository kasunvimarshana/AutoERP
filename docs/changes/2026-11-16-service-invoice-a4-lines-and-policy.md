# Keep A4 service invoice lines together and add approved policy text

The standard A4 Service Invoice now prints every saved line in invoice order under one Description of Goods / Services heading. Removed the labour and spare-parts group rows and their Invoice-owned grouping data because this presentation no longer needs them.

Aligned the payment/totals divider with the item table's description-column edge. Added the two policy lines supplied by the user between the payment/totals table and signatures. Other paper layouts, invoice amounts, and historical snapshots are unchanged.

Updated `output/pdf/service-invoice-a4-preview.pdf` from a representative four-line Service Invoice. The focused backend test passed (14 assertions); visual review confirmed the aligned divider, policy placement, and a single A4 page. Pint and `git diff --check` passed.
