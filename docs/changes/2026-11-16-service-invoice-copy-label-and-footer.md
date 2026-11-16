# Label every issued service invoice copy and align the A4 footer

Removed the Checked by signature from the A4 service invoice, leaving Customer and Cashier signatures evenly spaced. Placed the thank-you message and printed timestamp, printer, and copy label on one footer row beneath the signatures.

The first issued service invoice print is now marked Original and later issues Duplicate, including invoices with no payment yet. The existing invoice row lock and transaction protect that first-issue state against concurrent requests. Other invoice types and layouts keep their existing presentation.

Updated the A4 preview PDF and PNG. The focused A4 layout and unpaid copy issuance tests passed (2 tests, 23 assertions); visual review confirmed one A4 page and footer alignment.
