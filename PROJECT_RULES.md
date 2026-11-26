# AutoERP Permanent Project Rules for Developers and AI Agents

- **Author:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)

## Data Correctness and Careful Design

Data correctness is mandatory and the top priority across the entire system. Developers and AI agents must think carefully before designing, modifying, or implementing any schema, model, calculation, migration, or workflow that touches persistent data.

- **Think First:** Analyze all downstream data dependencies, business constraints, and ledger implications before writing code.
- **Evidence Over Guessing:** Never guess schema relationships, column definitions, or business rules. Validate against existing models and tests.
- **Zero Data Loss:** Every modification must protect historical audit trails, financial transactions, and operational records against corruption or loss.

## Laravel Database Migration Best Practices

All database schema changes must strictly follow Laravel migration best practices. The following rules are formulated as explicit MUST and MUST NOT requirements:

### 1. Migration Immutability

- **MUST NOT:** Never edit or alter a migration file once it has been committed, shared with team members, or deployed to any environment.
- **MUST:** Always create a new, separate migration file to modify, add, rename, or drop existing tables or columns.

### 2. Atomic Migrations

- **MUST:** Keep each migration strictly atomic, containing exactly one logical change (such as creating one table or adding one index).
- **MUST:** Assign each migration file a short, clear, descriptive name reflecting its single logical purpose (e.g., `create_orders_table`, `add_status_index_to_invoices_table`).
- **MUST NOT:** Do not bundle multiple unrelated table creations or schema modifications into a single migration file.

### 3. Reversible Rollbacks

- **MUST:** Always write a `down()` method that precisely and completely reverses every operation executed in `up()`.
- **MUST:** Verify that any faulty deployment can be safely rolled back using a single Artisan command:

```bash
php artisan migrate:rollback
```

- **MUST NOT:** Never leave the `down()` method empty, incomplete, or unimplemented.

### 4. Database-Driver Independence

- **MUST:** Always use Laravel's Schema Builder and Blueprint fluent methods to define schema changes:

```php
Schema::create('sample_records', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

- **MUST NOT:** Do not use raw SQL queries inside migration files, ensuring full database-driver portability across SQLite, MySQL, and PostgreSQL.

### 5. Database-Level Data Integrity Enforcement

- **MUST:** Explicitly define foreign key constraints using `constrained()`, enforce uniqueness with unique constraints, and apply `nullable()` modifiers explicitly in the database schema.
- **MUST NOT:** Do not rely exclusively on application-level validation to safeguard data integrity, because application validation alone can leave orphaned or inconsistent records during direct operations, background jobs, or concurrent writes.

### 6. Schema Consolidation in Mature Projects

- **MUST:** In mature projects with hundreds of accumulated migrations, consolidate the migration history into a single SQL schema file using the schema dump command:

```bash
php artisan schema:dump
```

- **MUST NOT:** Do not allow legacy migration bloat to degrade test execution speed or new environment bootstrapping performance.

### 7. Separation of Schema Changes and Data Seeding

- **MUST:** Keep schema definitions completely separate from data seeding operations.
- **MUST:** Use dedicated, idempotent Seeder classes with `updateOrCreate()` or `firstOrCreate()` to insert or manage initial configuration and test data:

```php
Role::firstOrCreate(['name' => 'admin']);
```

- **MUST NOT:** Never insert, update, or alter production data records inside a database migration file.

## Markdown Portability and Documentation Storage

Documentation must remain accessible, clear, and durable across all platforms, editors, and environments.

- **Portability Standards:** All Markdown documentation must follow CommonMark or GitHub Flavored Markdown (GFM) specifications to ensure clean rendering across editors, GitHub, and IDEs.
- **Unambiguous Syntax:** Use explicit ATX headings (`#`), fenced code blocks with language identifiers, and standard bulleted lists.
- **Linter Enforcement:** Maintain clean formatting that passes markdownlint rules (single H1, blank lines around block elements, no trailing whitespace, single trailing newline).
- **Storage Boundaries:**
  - **Durable Project Rules:** Permanent project rules live in this file (`PROJECT_RULES.md`).
  - **Temporary Notes:** Transient materials, including development plans, temporary TODOs, work logs, and preliminary research findings, must be stored in a separate directory such as `docs/notes/`.

## Project-Wide Audit and Code Signing Protocol

When requested, the AI agent must conduct a comprehensive, professional project audit and code signing routine.

- **Comprehensive Scope:** Perform a deep, end-to-end audit of the entire project, covering all modules, source files, application code, configuration files, and architectural boundaries.
- **Standardized Signature:** Consistently and professionally sign all relevant source files and code documentation with the authoritative author details:
  - **Author:** Kasun Vimarshana
  - **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
  - **Phone:** [+94713525253](tel:+94713525253)
- **File-Appropriate Comment Conventions:** Use the correct comment syntax for each file type:
  - PHP: `/** ... */` or `// ...`
  - TypeScript / JavaScript / CSS: `/** ... */` or `// ...`
  - Markdown: Standard metadata block under title or `## Author & Maintainer` section
  - Blade / HTML: `{{-- ... --}}` or `<!-- ... -->`
  - Shell / Python / YAML / Dockerfile: `# ...`
- **Zero Behavioral Regressions:** The audit and signing process must never modify business logic, runtime behavior, calculations, or application functionality.
