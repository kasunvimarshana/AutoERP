package store

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"time"

	"github.com/redis/go-redis/v9"
	"github.com/saga/payment-service/internal/model"
)

const (
	paymentTTL    = 24 * time.Hour
	keyPrefix     = "payment:"
	sagaKeyPrefix = "payment:saga:"
)

// RedisStore handles payment persistence in Redis.
type RedisStore struct {
	client *redis.Client
}

// NewRedisStore creates a new RedisStore and verifies connectivity.
func NewRedisStore(addr string) (*RedisStore, error) {
	client := redis.NewClient(&redis.Options{
		Addr:         addr,
		DialTimeout:  5 * time.Second,
		ReadTimeout:  3 * time.Second,
		WriteTimeout: 3 * time.Second,
		PoolSize:     10,
	})

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := client.Ping(ctx).Err(); err != nil {
		return nil, fmt.Errorf("redis ping failed: %w", err)
	}

	return &RedisStore{client: client}, nil
}

// SavePayment persists a payment and creates a saga_id → payment_id index.
func (s *RedisStore) SavePayment(ctx context.Context, p *model.Payment) error {
	data, err := json.Marshal(p)
	if err != nil {
		return fmt.Errorf("marshal payment: %w", err)
	}

	pipe := s.client.Pipeline()
	pipe.Set(ctx, keyPrefix+p.ID, data, paymentTTL)
	pipe.Set(ctx, sagaKeyPrefix+p.SagaID, p.ID, paymentTTL)

	_, err = pipe.Exec(ctx)
	return err
}

// GetPayment retrieves a payment by its ID.
func (s *RedisStore) GetPayment(ctx context.Context, paymentID string) (*model.Payment, error) {
	data, err := s.client.Get(ctx, keyPrefix+paymentID).Bytes()
	if err != nil {
		if errors.Is(err, redis.Nil) {
			return nil, fmt.Errorf("payment %s not found", paymentID)
		}
		return nil, err
	}

	var p model.Payment
	if err := json.Unmarshal(data, &p); err != nil {
		return nil, fmt.Errorf("unmarshal payment: %w", err)
	}
	return &p, nil
}

// GetPaymentBySagaID retrieves a payment using the saga ID index.
func (s *RedisStore) GetPaymentBySagaID(ctx context.Context, sagaID string) (*model.Payment, error) {
	paymentID, err := s.client.Get(ctx, sagaKeyPrefix+sagaID).Result()
	if err != nil {
		if errors.Is(err, redis.Nil) {
			return nil, fmt.Errorf("no payment found for saga %s", sagaID)
		}
		return nil, err
	}
	return s.GetPayment(ctx, paymentID)
}

// UpdatePaymentStatus updates only the status (and UpdatedAt) of an existing payment.
func (s *RedisStore) UpdatePaymentStatus(ctx context.Context, paymentID, status string) error {
	p, err := s.GetPayment(ctx, paymentID)
	if err != nil {
		return err
	}

	p.Status    = status
	p.UpdatedAt = time.Now().UTC()

	data, err := json.Marshal(p)
	if err != nil {
		return fmt.Errorf("marshal payment: %w", err)
	}

	return s.client.Set(ctx, keyPrefix+paymentID, data, paymentTTL).Err()
}
