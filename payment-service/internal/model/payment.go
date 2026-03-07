package model

import "time"

// Payment statuses.
const (
	StatusPending   = "pending"
	StatusProcessed = "processed"
	StatusRefunded  = "refunded"
	StatusFailed    = "failed"
)

// Payment represents a payment record stored in Redis.
type Payment struct {
	ID            string    `json:"id"`
	SagaID        string    `json:"saga_id"`
	OrderID       string    `json:"order_id"`
	CustomerID    string    `json:"customer_id"`
	Amount        float64   `json:"amount"`
	Status        string    `json:"status"`
	TransactionID string    `json:"transaction_id"`
	FailureReason string    `json:"failure_reason,omitempty"`
	CreatedAt     time.Time `json:"created_at"`
	UpdatedAt     time.Time `json:"updated_at"`
}
