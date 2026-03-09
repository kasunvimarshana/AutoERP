#!/usr/bin/env bash
# =============================================================================
# init-db.sh — PostgreSQL Initialization Script
# Runs inside the postgres-auth container at first boot
# (mounted to /docker-entrypoint-initdb.d/)
#
# For postgres-inventory and postgres-orders, run equivalent scripts
# OR use this script extended with multi-host logic when called externally.
# =============================================================================

set -euo pipefail

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
log()  { echo "[init-db] $*"; }
warn() { echo "[init-db][WARN] $*" >&2; }
die()  { echo "[init-db][ERROR] $*" >&2; exit 1; }

run_psql() {
    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" "$@"
}

run_psql_db() {
    local db="$1"; shift
    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$db" "$@"
}

# ---------------------------------------------------------------------------
# 1. Extensions
# ---------------------------------------------------------------------------
log "Enabling extensions on ${POSTGRES_DB}..."
run_psql <<-EOSQL
    CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
    CREATE EXTENSION IF NOT EXISTS "pgcrypto";
    CREATE EXTENSION IF NOT EXISTS "pg_trgm";
    CREATE EXTENSION IF NOT EXISTS "btree_gin";
EOSQL

# ---------------------------------------------------------------------------
# 2. Schemas (multi-tenant: public + per-tenant schema support)
# ---------------------------------------------------------------------------
log "Creating schemas..."
run_psql <<-EOSQL
    -- System schema for cross-tenant tables
    CREATE SCHEMA IF NOT EXISTS system AUTHORIZATION ${POSTGRES_USER};

    -- Audit schema for event sourcing / audit log
    CREATE SCHEMA IF NOT EXISTS audit AUTHORIZATION ${POSTGRES_USER};

    -- Default tenant schema (additional tenant schemas provisioned dynamically)
    CREATE SCHEMA IF NOT EXISTS tenant_default AUTHORIZATION ${POSTGRES_USER};
EOSQL

# ---------------------------------------------------------------------------
# 3. Roles & Users
# ---------------------------------------------------------------------------
log "Creating application roles..."
run_psql <<-EOSQL
    -- Read-only role (for reporting / analytics)
    DO \$\$
    BEGIN
        IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'readonly_role') THEN
            CREATE ROLE readonly_role NOLOGIN;
        END IF;
    END
    \$\$;

    -- Read-write role (application connections)
    DO \$\$
    BEGIN
        IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'readwrite_role') THEN
            CREATE ROLE readwrite_role NOLOGIN;
        END IF;
    END
    \$\$;

    -- Migration role (runs Flyway / Laravel artisan migrate)
    DO \$\$
    BEGIN
        IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'migration_role') THEN
            CREATE ROLE migration_role NOLOGIN;
        END IF;
    END
    \$\$;
EOSQL

# ---------------------------------------------------------------------------
# 4. Grant Schema Privileges
# ---------------------------------------------------------------------------
log "Granting schema privileges..."
run_psql <<-EOSQL
    -- readonly_role: SELECT on all schemas
    GRANT USAGE ON SCHEMA public, system, audit, tenant_default TO readonly_role;
    ALTER DEFAULT PRIVILEGES IN SCHEMA public      GRANT SELECT ON TABLES TO readonly_role;
    ALTER DEFAULT PRIVILEGES IN SCHEMA system      GRANT SELECT ON TABLES TO readonly_role;
    ALTER DEFAULT PRIVILEGES IN SCHEMA audit       GRANT SELECT ON TABLES TO readonly_role;
    ALTER DEFAULT PRIVILEGES IN SCHEMA tenant_default GRANT SELECT ON TABLES TO readonly_role;

    -- readwrite_role: full DML on public + tenant schemas
    GRANT USAGE, CREATE ON SCHEMA public, tenant_default TO readwrite_role;
    ALTER DEFAULT PRIVILEGES IN SCHEMA public         GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES    TO readwrite_role;
    ALTER DEFAULT PRIVILEGES IN SCHEMA public         GRANT USAGE, SELECT, UPDATE          ON SEQUENCES TO readwrite_role;
    ALTER DEFAULT PRIVILEGES IN SCHEMA tenant_default GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES    TO readwrite_role;
    ALTER DEFAULT PRIVILEGES IN SCHEMA tenant_default GRANT USAGE, SELECT, UPDATE          ON SEQUENCES TO readwrite_role;

    -- migration_role: DDL rights
    GRANT CREATE ON SCHEMA public, system, audit, tenant_default TO migration_role;
    GRANT readwrite_role TO migration_role;
    GRANT readonly_role  TO migration_role;

    -- Grant readwrite to the application DB user
    GRANT readwrite_role TO ${POSTGRES_USER};
    GRANT migration_role TO ${POSTGRES_USER};
EOSQL

# ---------------------------------------------------------------------------
# 5. Audit Log Table (shared across services via FDW or per-service copy)
# ---------------------------------------------------------------------------
log "Creating audit log structure..."
run_psql <<-EOSQL
    CREATE TABLE IF NOT EXISTS audit.audit_log (
        id             BIGSERIAL    PRIMARY KEY,
        event_id       UUID         NOT NULL DEFAULT uuid_generate_v4(),
        tenant_id      UUID,
        user_id        UUID,
        service_name   VARCHAR(100) NOT NULL,
        action         VARCHAR(100) NOT NULL,
        resource_type  VARCHAR(100),
        resource_id    VARCHAR(255),
        old_value      JSONB,
        new_value      JSONB,
        ip_address     INET,
        user_agent     TEXT,
        correlation_id UUID,
        created_at     TIMESTAMPTZ  NOT NULL DEFAULT NOW()
    );

    CREATE INDEX IF NOT EXISTS idx_audit_log_tenant_id    ON audit.audit_log (tenant_id);
    CREATE INDEX IF NOT EXISTS idx_audit_log_user_id      ON audit.audit_log (user_id);
    CREATE INDEX IF NOT EXISTS idx_audit_log_created_at   ON audit.audit_log (created_at DESC);
    CREATE INDEX IF NOT EXISTS idx_audit_log_correlation  ON audit.audit_log (correlation_id);
    CREATE INDEX IF NOT EXISTS idx_audit_log_resource     ON audit.audit_log (resource_type, resource_id);

    COMMENT ON TABLE audit.audit_log IS 'Immutable audit trail for all domain actions across the platform.';
EOSQL

# ---------------------------------------------------------------------------
# 6. System Tables (tenants registry, feature flags)
# ---------------------------------------------------------------------------
log "Creating system tables..."
run_psql <<-EOSQL
    CREATE TABLE IF NOT EXISTS system.tenants (
        id            UUID          PRIMARY KEY DEFAULT uuid_generate_v4(),
        slug          VARCHAR(63)   NOT NULL UNIQUE,
        name          VARCHAR(255)  NOT NULL,
        plan          VARCHAR(50)   NOT NULL DEFAULT 'starter',
        status        VARCHAR(20)   NOT NULL DEFAULT 'active',
        schema_name   VARCHAR(63)   NOT NULL,
        settings      JSONB         NOT NULL DEFAULT '{}',
        metadata      JSONB         NOT NULL DEFAULT '{}',
        created_at    TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
        updated_at    TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
        deleted_at    TIMESTAMPTZ,
        CONSTRAINT tenants_status_check CHECK (status IN ('active','suspended','cancelled','trial'))
    );

    CREATE INDEX IF NOT EXISTS idx_tenants_slug       ON system.tenants (slug);
    CREATE INDEX IF NOT EXISTS idx_tenants_status     ON system.tenants (status);
    CREATE INDEX IF NOT EXISTS idx_tenants_deleted_at ON system.tenants (deleted_at) WHERE deleted_at IS NULL;

    CREATE TABLE IF NOT EXISTS system.feature_flags (
        id           BIGSERIAL    PRIMARY KEY,
        tenant_id    UUID         REFERENCES system.tenants(id) ON DELETE CASCADE,
        flag_key     VARCHAR(100) NOT NULL,
        is_enabled   BOOLEAN      NOT NULL DEFAULT FALSE,
        rollout_pct  SMALLINT     NOT NULL DEFAULT 0 CHECK (rollout_pct BETWEEN 0 AND 100),
        metadata     JSONB        NOT NULL DEFAULT '{}',
        created_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
        updated_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
        UNIQUE (tenant_id, flag_key)
    );

    COMMENT ON TABLE system.tenants       IS 'Central registry of all SaaS tenants.';
    COMMENT ON TABLE system.feature_flags IS 'Per-tenant feature flag overrides.';
EOSQL

# ---------------------------------------------------------------------------
# 7. Outbox Table (Transactional Outbox Pattern for reliable event publishing)
# ---------------------------------------------------------------------------
log "Creating outbox table..."
run_psql <<-EOSQL
    CREATE TABLE IF NOT EXISTS public.outbox_events (
        id             UUID         PRIMARY KEY DEFAULT uuid_generate_v4(),
        aggregate_type VARCHAR(100) NOT NULL,
        aggregate_id   VARCHAR(255) NOT NULL,
        event_type     VARCHAR(150) NOT NULL,
        payload        JSONB        NOT NULL,
        tenant_id      UUID,
        correlation_id UUID,
        causation_id   UUID,
        published      BOOLEAN      NOT NULL DEFAULT FALSE,
        published_at   TIMESTAMPTZ,
        retry_count    SMALLINT     NOT NULL DEFAULT 0,
        error          TEXT,
        created_at     TIMESTAMPTZ  NOT NULL DEFAULT NOW()
    );

    CREATE INDEX IF NOT EXISTS idx_outbox_unpublished ON public.outbox_events (published, created_at)
        WHERE published = FALSE;
    CREATE INDEX IF NOT EXISTS idx_outbox_aggregate   ON public.outbox_events (aggregate_type, aggregate_id);
    CREATE INDEX IF NOT EXISTS idx_outbox_event_type  ON public.outbox_events (event_type);

    COMMENT ON TABLE public.outbox_events IS 'Transactional outbox for reliable at-least-once event delivery to message brokers.';
EOSQL

# ---------------------------------------------------------------------------
# 8. Seed: Default tenant for local development
# ---------------------------------------------------------------------------
log "Seeding default development tenant..."
run_psql <<-EOSQL
    INSERT INTO system.tenants (id, slug, name, plan, status, schema_name, settings)
    VALUES (
        '00000000-0000-0000-0000-000000000001',
        'default',
        'Default Development Tenant',
        'enterprise',
        'active',
        'tenant_default',
        '{"timezone":"UTC","currency":"USD","locale":"en-US"}'
    )
    ON CONFLICT (slug) DO NOTHING;
EOSQL

log "Database initialization complete for ${POSTGRES_DB}."

# ---------------------------------------------------------------------------
# 9. Print Summary
# ---------------------------------------------------------------------------
run_psql <<-EOSQL
    SELECT
        current_database()  AS database,
        current_user        AS connected_user,
        version()           AS pg_version;

    SELECT schema_name
    FROM information_schema.schemata
    WHERE schema_name NOT IN ('pg_catalog','pg_toast','information_schema')
    ORDER BY schema_name;
EOSQL

log "✓ All initialization steps completed successfully."
