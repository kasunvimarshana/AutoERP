# Show Add line for assigned count offers

The manual Add line action was limited to card-backed offer assignments. The job screen also shows standalone count offer assignments, including the Wash and Vacuum offer, so those had no Add line action even though their paid item could be added through the normal job line flow.

The action now appears for any assigned count offer when the job can add lines and that paid item is not already on the job. Card assignment, invoice counting, and the existing line API remain unchanged. Added a focused UI test for the standalone assignment case.
