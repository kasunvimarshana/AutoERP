package service

import (
	"context"

	"github.com/saga/payment-service/internal/model"
)

// PaymentStore defines the storage operations needed by PaymentService.
type PaymentStore interface {
	SavePayment(ctx context.Context, p *model.Payment) error
	GetPayment(ctx context.Context, paymentID string) (*model.Payment, error)
	GetPaymentBySagaID(ctx context.Context, sagaID string) (*model.Payment, error)
	UpdatePaymentStatus(ctx context.Context, paymentID, status string) error
}
