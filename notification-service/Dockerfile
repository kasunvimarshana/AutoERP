FROM node:20-alpine

RUN apk add --no-cache wget

# Create non-root user
RUN addgroup -g 1001 -S appgroup && \
    adduser  -u 1001 -S appuser  -G appgroup

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY src/ ./src/

RUN chown -R appuser:appgroup /app

USER appuser

EXPOSE 8003

HEALTHCHECK --interval=15s --timeout=5s --start-period=20s --retries=3 \
    CMD wget --spider -q http://localhost:8003/health || exit 1

CMD ["node", "src/index.js"]
