# Merge promotion-card work into worktree-0.0.8

Merged `sadheera/prormotion-card` into the refreshed `worktree-0.0.8` branch, bringing promotion card and offer changes, service job list improvements, customer address entry, A4 service invoice printing, dashboard sales insights, and the header Quick Access dropdown.

Resolved the two header merge conflicts by retaining the target branch's breadcrumb home path and profile menu behavior alongside the new Quick Access dropdown and its filtered navigation input. No migration files came from the promotion-card branch; the target branch's existing migration history remains unchanged by this merge.

Verification: frontend TypeScript typecheck and 24 affected UI tests passed. Invoice, Customer, Vehicle Service, and Reporting backend tests passed (89 tests, 667 assertions). The full backend suite was started but stopped after several minutes without output; the focused suite then completed. Merge diff checks found no source-code whitespace errors or remaining conflict markers.
