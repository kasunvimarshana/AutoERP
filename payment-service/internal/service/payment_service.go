package service

import (
	"context"
	"fmt"
	"log"
	"math/rand"
	"time"

	"github.com/google/uuid"
	"github.com/saga/payment-service/internal/model"
)

// Publisher is a minimal interface for publishing reply messages.
type Publisher interface {
	PublishReply(ctx context.Context, exchange, routingKey string, message interface{}) error
}

// PaymentService handles all payment business logic.
type PaymentService struct {
	store     PaymentStore
	publisher Publisher
}

// NewPaymentService creates a new PaymentService.
func NewPaymentService(s PaymentStore, pub Publisher) *PaymentService {
	return &PaymentService{store: s, publisher: pub}
}

// ProcessPayment validates, creates and attempts to process a payment.
// It simulates a 90% success rate to demonstrate saga compensation.
func (svc *PaymentService) ProcessPayment(
	ctx context.Context,
	sagaID, orderID, customerID string,
	amount float64,
) (*model.Payment, error) {
	if amount <= 0 {
		return nil, fmt.Errorf("payment amount must be greater than zero, got %.2f", amount)
	}
	if sagaID == "" {
		return nil, fmt.Errorf("saga_id is required")
	}
	if orderID == "" {
		return nil, fmt.Errorf("order_id is required")
	}

	payment := &model.Payment{
		ID:            uuid.New().String(),
		SagaID:        sagaID,
		OrderID:       orderID,
		CustomerID:    customerID,
		Amount:        amount,
		Status:        model.StatusPending,
		TransactionID: "",
		CreatedAt:     time.Now().UTC(),
		UpdatedAt:     time.Now().UTC(),
	}

	// Simulate payment processing with 90% success rate.
	// In production this would call a real payment gateway.
	// rand.Float64 is safe for concurrent use since Go 1.20.
	if rand.Float64() < 0.90 { //nolint:gosec
		payment.Status        = model.StatusProcessed
		payment.TransactionID = fmt.Sprintf("TXN-%s", uuid.New().String()[:8])
		log.Printf("[PaymentService] Payment processed: saga=%s txn=%s amount=%.2f",
			sagaID, payment.TransactionID, amount)
	} else {
		payment.Status        = model.StatusFailed
		payment.FailureReason = "Card declined by issuing bank"
		log.Printf("[PaymentService] Payment failed (simulated): saga=%s", sagaID)
	}

	payment.UpdatedAt = time.Now().UTC()

	if err := svc.store.SavePayment(ctx, payment); err != nil {
		return nil, fmt.Errorf("failed to persist payment: %w", err)
	}

	if payment.Status == model.StatusFailed {
		return payment, fmt.Errorf("%s", payment.FailureReason)
	}

	return payment, nil
}

// RefundPayment reverses a previously processed payment.
func (svc *PaymentService) RefundPayment(ctx context.Context, sagaID, orderID string) error {
	payment, err := svc.store.GetPaymentBySagaID(ctx, sagaID)
	if err != nil {
		return fmt.Errorf("payment not found for saga %s: %w", sagaID, err)
	}

	if payment.Status != model.StatusProcessed {
		return fmt.Errorf("payment %s cannot be refunded: current status is %s", payment.ID, payment.Status)
	}

	if err := svc.store.UpdatePaymentStatus(ctx, payment.ID, model.StatusRefunded); err != nil {
		return fmt.Errorf("failed to update payment status to refunded: %w", err)
	}

	log.Printf("[PaymentService] Payment refunded: saga=%s payment=%s", sagaID, payment.ID)
	return nil
}

// GetPayment retrieves a payment by its ID.
func (svc *PaymentService) GetPayment(ctx context.Context, paymentID string) (*model.Payment, error) {
	return svc.store.GetPayment(ctx, paymentID)
}

// GetPaymentBySagaID retrieves a payment by saga ID.
func (svc *PaymentService) GetPaymentBySagaID(ctx context.Context, sagaID string) (*model.Payment, error) {
	return svc.store.GetPaymentBySagaID(ctx, sagaID)
}
