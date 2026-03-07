# =============================================================================
# Nginx API Gateway — Dockerfile
# Multi-Tenant SaaS Inventory Management System
# =============================================================================
# Builds a minimal, hardened Nginx image that:
#   • Uses the official nginx:alpine base (small attack surface)
#   • Drops the default config and uses our custom nginx.conf
#   • Installs curl for Docker healthchecks
#   • Runs as a non-root user for improved container security
# =============================================================================

FROM nginx:1.27-alpine AS base

LABEL maintainer="SaaS Platform Team"
LABEL description="Nginx API Gateway for the Multi-Tenant SaaS Inventory System"

# Install curl (healthcheck) and remove default nginx config
RUN apk add --no-cache curl \
    && rm -f /etc/nginx/conf.d/default.conf \
    && rm -f /etc/nginx/nginx.conf

# Create directories for optional conf.d overrides and SSL certs
RUN mkdir -p /etc/nginx/conf.d \
             /etc/nginx/ssl \
             /var/cache/nginx \
             /var/log/nginx

# Copy the gateway configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Create a non-root user for nginx worker processes.
# The master process still needs to bind to port 80/443 (requires root or
# CAP_NET_BIND_SERVICE), so we keep the master as root but drop workers via
# the 'user' directive inside nginx.conf if desired.
# Here we simply ensure the log/cache directories are writable.
RUN chown -R nginx:nginx /var/cache/nginx /var/log/nginx \
    && chmod -R 755 /var/cache/nginx /var/log/nginx

# Expose ports
EXPOSE 80 443

# Validate config at build time — fails the build if nginx.conf has errors
RUN nginx -t

# Forward logs to Docker log collector
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

HEALTHCHECK --interval=30s --timeout=10s --start-period=15s --retries=3 \
    CMD curl -sf http://localhost/healthz || exit 1

CMD ["nginx", "-g", "daemon off;"]
