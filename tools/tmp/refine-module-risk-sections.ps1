Set-Location "d:/projects/KVAutoERP"

$moduleRiskMap = @{
    "Audit" = @{
        current = "Cross-module audit event coverage is uneven, with potential blind spots in async/background flows."
        debt = "Payload schemas for audit metadata are loosely standardized, which can hinder reliable downstream analytics."
        plan = "Define event-level audit contracts and add contract tests for high-value write paths."
    }
    "Auth" = @{
        current = "Token/session lifecycle edge cases (refresh, revocation, SSO callback failure) may not be uniformly hardened across providers."
        debt = "Authorization strategy composition (ABAC/RBAC paths) is spread across services without a single policy map artifact."
        plan = "Publish a centralized auth policy matrix and add endpoint tests for denial/expiry/replay scenarios."
    }
    "Configuration" = @{
        current = "Reference-data drift risk exists if seed/update workflows are not versioned with clear compatibility rules."
        debt = "Cross-module assumptions on configuration defaults are implicit rather than encoded as explicit contracts."
        plan = "Version reference datasets and add compatibility checks for downstream module bootstrapping."
    }
    "Core" = @{
        current = "Shared primitives can become an unintended dependency hub if module-specific logic leaks into core abstractions."
        debt = "Base abstractions are broad, making it difficult to reason about allowed extension points and invariants."
        plan = "Narrow and document Core extension seams; enforce no-business-logic guardrails in architecture tests."
    }
    "Customer" = @{
        current = "Address/contact integrity can drift without stronger uniqueness and lifecycle policies per customer context."
        debt = "Nested write flows rely on service conventions more than explicit domain invariants for contact/address cardinality."
        plan = "Add invariants and tests for primary-contact/address rules and customer merge/deactivation workflows."
    }
    "Employee" = @{
        current = "Employee lifecycle transitions (active, suspended, terminated) are not strongly modeled as state contracts."
        debt = "Cross-links to HR flows are convention-based with limited explicit boundary documentation."
        plan = "Introduce explicit lifecycle-state rules and align Employee-HR boundary contracts with integration tests."
    }
    "Finance" = @{
        current = "Posting/approval workflows remain sensitive to status drift and cross-module event ordering under load."
        debt = "Journal and subledger invariants are distributed across services, increasing regression risk during refactors."
        plan = "Codify posting invariants as reusable validators and expand end-to-end posting consistency tests."
    }
    "HR" = @{
        current = "Status normalization remains a top risk across leave, payroll, and performance workflows."
        debt = "HR-to-Finance posting dependencies are operationally coupled with limited contract-level guarantees."
        plan = "Standardize status strategy and add contract tests for payroll-to-journal posting flows."
    }
    "Inventory" = @{
        current = "Concurrency on reservations, transfers, and adjustments can still create contention and replay/idempotency edge cases."
        debt = "Cost-layer and movement traceability rules are complex and dispersed across repositories/services."
        plan = "Expand lock/idempotency test coverage and formalize valuation + traceability invariants in architecture docs/tests."
    }
    "OrganizationUnit" = @{
        current = "Hierarchy and scope-sharing rules can drift without stricter constraints for parent/child and cross-unit visibility."
        debt = "Attachment/user-assignment policies are implemented but not fully captured as explicit domain constraints."
        plan = "Add hierarchy integrity constraints and tests for scope inheritance and shared-unit boundary behavior."
    }
    "Pricing" = @{
        current = "Price resolution precedence and overlap handling can produce ambiguous outcomes in multi-list scenarios."
        debt = "Temporal validity and conflict resolution rules are implicit in service logic rather than a formal policy model."
        plan = "Define deterministic price-precedence contracts and add conflict/overlap regression suites."
    }
    "Product" = @{
        current = "Variant/attribute combinatorics and identifier uniqueness can regress without strict invariants at scale."
        debt = "Catalog consistency rules across product, variant, identifier, and UOM conversion are spread across multiple services."
        plan = "Consolidate catalog invariants and add high-volume mutation tests for variant and identifier integrity."
    }
    "Purchase" = @{
        current = "Procure-to-pay document state transitions remain vulnerable to out-of-order operations and partial posting failures."
        debt = "Cross-module touchpoints (inventory receipts, finance postings) rely on event timing assumptions."
        plan = "Add sequence-aware integration tests for PO->GRN->Invoice->Payment paths including retry/failure branches."
    }
    "Sales" = @{
        current = "Order-to-cash transitions can diverge when shipment/invoice/payment events arrive out of sequence."
        debt = "Return and credit interactions with inventory/finance are distributed across handlers without a unified flow contract."
        plan = "Define O2C sequence contracts and add resilience tests for partial shipment, return, and payment reconciliation."
    }
    "Shared" = @{
        current = "As an intentionally thin shell, Shared risks accidental accumulation of runtime logic over time."
        debt = "Shared boundaries are policy-driven but not fully enforced by dedicated anti-bloat checks."
        plan = "Add explicit anti-bloat guardrails to keep Shared limited to provider/route surface concerns."
    }
    "Supplier" = @{
        current = "Supplier-product linkage integrity may drift without tighter constraints on active sourcing and duplicates."
        debt = "Supplier master lifecycle policies (activation, suspension, archival) are not yet fully codified."
        plan = "Add sourcing integrity constraints and lifecycle policy tests for supplier-product associations."
    }
    "Tax" = @{
        current = "Tax resolution correctness is sensitive to rule precedence and effective-date overlap conditions."
        debt = "Jurisdiction/rule composition strategy is service-centric without a formalized decision trace model."
        plan = "Add deterministic rule-evaluation contracts and regression tests for edge precedence/date windows."
    }
    "Tenant" = @{
        current = "Tenant config/domain/plan mutations can create platform-wide impact if consistency checks are incomplete."
        debt = "Tenant configuration schema evolution lacks a strict compatibility matrix across modules."
        plan = "Introduce versioned tenant-config contracts and compatibility checks in module bootstrap tests."
    }
    "User" = @{
        current = "Role/permission assignment drift can occur without stronger least-privilege and revocation verification paths."
        debt = "User profile/device/security workflows are broad, with some policy rules encoded only at service level."
        plan = "Expand authorization regression tests and formalize role-permission invariants with contract-level checks."
    }
    "Warehouse" = @{
        current = "Location hierarchy and movement attribution can degrade if hierarchy constraints are bypassed in write paths."
        debt = "Warehouse-location governance rules are partially implicit across services/repositories."
        plan = "Enforce hierarchy invariants and add route/service integration tests for movement attribution correctness."
    }
}

$docs = Get-ChildItem "docs/architecture/modules" -File -Filter "*.md" | Where-Object { $_.Name -ne "README.md" }

foreach ($doc in $docs) {
    $moduleName = [System.IO.Path]::GetFileNameWithoutExtension($doc.Name)
    if (-not $moduleRiskMap.ContainsKey($moduleName)) {
        continue
    }

    $risk = $moduleRiskMap[$moduleName]
    $replacement = @(
        "## 10. Open Risks and Refactor Backlog",
        "- Current risks: $($risk.current)",
        "- Technical debt: $($risk.debt)",
        "- Planned refactors: $($risk.plan)",
        ""
    ) -join [Environment]::NewLine

    $content = Get-Content $doc.FullName -Raw
    $updated = [regex]::Replace(
        $content,
        "## 10\. Open Risks and Refactor Backlog[\s\S]*?\r?\n## 11\. Concrete Source Map",
        $replacement + "## 11. Concrete Source Map"
    )

    Set-Content -Path $doc.FullName -Value $updated -Encoding utf8
}
