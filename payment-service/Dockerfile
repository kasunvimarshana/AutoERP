# ─── Build stage ─────────────────────────────────────────────────────────────
FROM golang:1.21-alpine AS builder

RUN apk add --no-cache git ca-certificates tzdata

WORKDIR /build

COPY go.mod go.sum* ./
RUN go mod download

COPY . .

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 \
    go build -ldflags="-w -s" -o payment-service ./main.go

# ─── Runtime stage ───────────────────────────────────────────────────────────
FROM alpine:3.19

RUN apk add --no-cache ca-certificates tzdata wget

# Create non-root user
RUN addgroup -g 1001 -S appgroup && \
    adduser  -u 1001 -S appuser -G appgroup

WORKDIR /app

COPY --from=builder /build/payment-service .

RUN chown appuser:appgroup /app/payment-service

USER appuser

EXPOSE 8002

HEALTHCHECK --interval=15s --timeout=5s --start-period=20s --retries=3 \
    CMD wget --spider -q http://localhost:8002/health || exit 1

CMD ["./payment-service"]
