# Move Quick Access from the sidebar into the header

Removed the fixed Quick Access panel from the tenant sidebar and added a header dropdown based on the approved reference. It groups Create Service Job and Service Job List under Vehicle Service, and Create Selling and Selling List under Selling. Desktop hover opens the menu; click and keyboard interaction support other devices. Selecting a link, pressing Escape, clicking outside, or changing routes closes it.

The dropdown reads the already filtered navigation sections passed by WorkspaceLayout, so existing module and permission rules remain the source of truth. The header shows no Quick Access control when none of its links are available; platform navigation is unchanged. Updated focused sidebar and dropdown tests.

Verification: seven layout tests passed; TypeScript typecheck, ESLint, and `git diff --check` passed.
