# Vehicle Service credit invoice receipt behavior

## Request

Check whether Vehicle Service jobs kept on customer credit generate a payment receipt.

## Findings

- The credit endpoint updates the service invoice link's settlement mode to `credit` and returns only a confirmation. It does not create a Payment record.
- The payment preparation page shows the receipt/payment success state and print/share actions only after direct payment creation. The credit path returns to the job page without those actions.
- Therefore, keeping a job invoice on credit does not generate a payment receipt. The invoice remains outstanding on the customer's account; a receipt is relevant only when a payment is later recorded.

## Changes

- No application code changed. Recorded the code-path finding for future reference.
