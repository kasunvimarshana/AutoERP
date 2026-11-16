# Show posted balance on service payment page

The payment page kept the selected invoice's prepayment balance in its settled invoice snapshot. After posting, the invoice left the eligible list, causing the page to show the old outstanding balance while method count, cash applied, and change incorrectly fell to zero.

After a direct payment posts, the page now calculates the displayed outstanding balance from the original balance and the payment's allocated amount. It retains the selected invoice as the basis for the locked payment summary, so method count, applied cash, and change remain visible. The settled message uses present tense after posting. Credit flow remains due on the customer account.

Added a regression test for a fully settled cash payment. Vehicle Service payment page tests, TypeScript typecheck, ESLint, and diff check passed.
