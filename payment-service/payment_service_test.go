package main

import (
	"context"
	"fmt"
	"testing"

	"github.com/saga/payment-service/internal/model"
	"github.com/saga/payment-service/internal/service"
)

// ─── Mock Store ───────────────────────────────────────────────────────────────

type mockStore struct {
	payments  map[string]*model.Payment
	sagaIndex map[string]string
}

func newMockStore() *mockStore {
	return &mockStore{
		payments:  make(map[string]*model.Payment),
		sagaIndex: make(map[string]string),
	}
}

func (m *mockStore) SavePayment(_ context.Context, p *model.Payment) error {
	clone := *p
	m.payments[p.ID] = &clone
	m.sagaIndex[p.SagaID] = p.ID
	return nil
}

func (m *mockStore) GetPayment(_ context.Context, paymentID string) (*model.Payment, error) {
	p, ok := m.payments[paymentID]
	if !ok {
		return nil, fmt.Errorf("payment %s not found", paymentID)
	}
	clone := *p
	return &clone, nil
}

func (m *mockStore) GetPaymentBySagaID(_ context.Context, sagaID string) (*model.Payment, error) {
	id, ok := m.sagaIndex[sagaID]
	if !ok {
		return nil, fmt.Errorf("no payment found for saga %s", sagaID)
	}
	return m.GetPayment(context.Background(), id)
}

func (m *mockStore) UpdatePaymentStatus(_ context.Context, paymentID, status string) error {
	p, ok := m.payments[paymentID]
	if !ok {
		return fmt.Errorf("payment %s not found", paymentID)
	}
	p.Status = status
	return nil
}

// ─── Mock Publisher ───────────────────────────────────────────────────────────

type mockPublisher struct {
	published []interface{}
}

func (mp *mockPublisher) PublishReply(_ context.Context, _, _ string, msg interface{}) error {
	mp.published = append(mp.published, msg)
	return nil
}

// ─── Helper ───────────────────────────────────────────────────────────────────

func newTestPaymentService(st service.PaymentStore, pub service.Publisher) *service.PaymentService {
	return service.NewPaymentService(st, pub)
}

// ─── Tests ────────────────────────────────────────────────────────────────────

func TestProcessPaymentWithValidAmount(t *testing.T) {
	ctx := context.Background()

	successCount := 0
	attempts     := 20

	for i := 0; i < attempts; i++ {
		st  := newMockStore()
		pub := &mockPublisher{}
		svc := newTestPaymentService(st, pub)

		sagaID  := fmt.Sprintf("saga-test-%d", i)
		payment, err := svc.ProcessPayment(ctx, sagaID, "order-001", "customer-001", 100.00)

		if err == nil {
			if payment == nil {
				t.Error("expected non-nil payment on success")
				continue
			}
			if payment.Status != model.StatusProcessed {
				t.Errorf("expected status %s, got %s", model.StatusProcessed, payment.Status)
			}
			if payment.TransactionID == "" {
				t.Error("expected non-empty transaction ID on success")
			}
			successCount++
		} else {
			// On failure, payment object should still be returned
			if payment == nil {
				t.Error("expected non-nil payment even on failure")
			}
		}
	}

	// With 90% success rate and 20 attempts, expect at least 5 successes
	if successCount < 5 {
		t.Errorf("expected at least 5 successes out of %d attempts, got %d (90%% success rate)", attempts, successCount)
	}
}

func TestProcessPaymentWithZeroAmount(t *testing.T) {
	ctx := context.Background()
	svc := newTestPaymentService(newMockStore(), &mockPublisher{})

	payment, err := svc.ProcessPayment(ctx, "saga-zero", "order-zero", "cust-001", 0)

	if err == nil {
		t.Error("expected error for zero amount, got nil")
	}
	if payment != nil {
		t.Error("expected nil payment for zero amount")
	}
}

func TestProcessPaymentWithNegativeAmount(t *testing.T) {
	ctx := context.Background()
	svc := newTestPaymentService(newMockStore(), &mockPublisher{})

	payment, err := svc.ProcessPayment(ctx, "saga-neg", "order-neg", "cust-001", -50.00)

	if err == nil {
		t.Error("expected error for negative amount")
	}
	if payment != nil {
		t.Error("expected nil payment for negative amount")
	}
}

func TestRefundPayment(t *testing.T) {
	ctx := context.Background()
	st  := newMockStore()
	svc := newTestPaymentService(st, &mockPublisher{})

	// Insert a pre-processed payment directly into the mock store
	p := &model.Payment{
		ID:            "pay-001",
		SagaID:        "saga-refund-test",
		OrderID:       "order-001",
		CustomerID:    "customer-001",
		Amount:        250.00,
		Status:        model.StatusProcessed,
		TransactionID: "TXN-ABCD1234",
	}
	st.SavePayment(ctx, p) //nolint:errcheck

	err := svc.RefundPayment(ctx, "saga-refund-test", "order-001")
	if err != nil {
		t.Fatalf("expected no error on refund, got: %v", err)
	}

	updated, err := st.GetPayment(ctx, "pay-001")
	if err != nil {
		t.Fatalf("failed to retrieve payment: %v", err)
	}
	if updated.Status != model.StatusRefunded {
		t.Errorf("expected status %s, got %s", model.StatusRefunded, updated.Status)
	}
}

func TestRefundPaymentAlreadyRefunded(t *testing.T) {
	ctx := context.Background()
	st  := newMockStore()
	svc := newTestPaymentService(st, &mockPublisher{})

	p := &model.Payment{
		ID:         "pay-002",
		SagaID:     "saga-already-refunded",
		OrderID:    "order-002",
		CustomerID: "customer-001",
		Amount:     100.00,
		Status:     model.StatusRefunded,
	}
	st.SavePayment(ctx, p) //nolint:errcheck

	err := svc.RefundPayment(ctx, "saga-already-refunded", "order-002")
	if err == nil {
		t.Error("expected error when refunding already-refunded payment")
	}
}

func TestProcessPaymentMissingFields(t *testing.T) {
	ctx := context.Background()
	svc := newTestPaymentService(newMockStore(), &mockPublisher{})

	_, err := svc.ProcessPayment(ctx, "", "order-001", "cust-001", 100.00)
	if err == nil {
		t.Error("expected error for empty saga_id")
	}

	_, err = svc.ProcessPayment(ctx, "saga-001", "", "cust-001", 100.00)
	if err == nil {
		t.Error("expected error for empty order_id")
	}
}
