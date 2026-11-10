# Sidebar quick access for service jobs and selling

**Date:** 2026-11-10

Added a fixed Quick access section below the sidebar brand with Create Service Job, Service Job List, Create Selling, and Selling List. The main navigation remains independently scrollable, so these frequent actions stay visible without expanding their modules.

The shortcuts reuse links from the already filtered navigation tree. Their destinations and access rules therefore remain owned by the existing navigation configuration, and unavailable actions do not appear as shortcuts. The same links work in the mobile drawer and collapsed sidebar, where link names remain available to assistive technology.

Verified with the sidebar Vitest suite, TypeScript typecheck, ESLint on the changed frontend files, and `git diff --check`.
