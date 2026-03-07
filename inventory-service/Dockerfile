FROM node:20-alpine

# Install dumb-init for proper signal handling
RUN apk add --no-cache dumb-init

WORKDIR /app

# Copy package files for Docker layer caching
COPY package*.json ./

# Install production dependencies
RUN npm ci --only=production

# Copy source code
COPY . .

# Copy .env.example as .env if not present (will be overridden by docker-compose env)
RUN cp .env.example .env 2>/dev/null || true

# Create non-root user
RUN addgroup -g 1001 -S nodejs && adduser -S nodejs -u 1001
USER nodejs

EXPOSE 8002

HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
  CMD wget -qO- http://localhost:8002/ || exit 1

ENTRYPOINT ["dumb-init", "--"]
CMD ["node", "src/app.js"]
