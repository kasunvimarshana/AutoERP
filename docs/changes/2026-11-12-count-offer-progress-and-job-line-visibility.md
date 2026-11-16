# Count offer progress and job-line action visibility

When a count offer has earned an unused free reward, its job tile now shows the paid visit cycle as complete, including filled progress marks. A free reward already added to the current job is labeled "Free line added" while the paid cycle remains complete until invoicing; any progress in the next repeat cycle is shown separately.

The count offer's paid "Add line" action is hidden whenever its qualifying item is already in the job lines. The create form supplies item IDs from the same line payload used to save the draft, including promotion lines. The saved job page checks all non-cancelled item lines. This affects presentation and duplicate-action visibility only; qualification and redemption remain governed by posted invoices.

Focused promotion and job-form tests passed (17 tests). ESLint passed for the touched frontend files.
