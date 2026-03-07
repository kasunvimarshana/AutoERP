package handler

import (
	"context"
	"log"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/saga/payment-service/internal/model"
)

// PaymentServiceInterface defines the service operations needed by the handler.
type PaymentServiceInterface interface {
	ProcessPayment(ctx context.Context, sagaID, orderID, customerID string, amount float64) (*model.Payment, error)
	RefundPayment(ctx context.Context, sagaID, orderID string) error
	GetPayment(ctx context.Context, paymentID string) (*model.Payment, error)
	GetPaymentBySagaID(ctx context.Context, sagaID string) (*model.Payment, error)
}

// PaymentHandler holds HTTP handler methods for the payment service.
type PaymentHandler struct {
	svc PaymentServiceInterface
}

// NewPaymentHandler creates a new PaymentHandler.
func NewPaymentHandler(svc PaymentServiceInterface) *PaymentHandler {
	return &PaymentHandler{svc: svc}
}

// HealthCheck returns service status.
func (h *PaymentHandler) HealthCheck(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"status":  "ok",
		"service": "payment-service",
		"version": "1.0.0",
		"time":    time.Now().UTC().Format(time.RFC3339),
	})
}

// GetPayment retrieves a payment by ID.
// GET /payments/:id
func (h *PaymentHandler) GetPayment(c *gin.Context) {
	id := c.Param("id")

	payment, err := h.svc.GetPayment(c.Request.Context(), id)
	if err != nil {
		log.Printf("[Handler] GetPayment error: %v", err)
		c.JSON(http.StatusNotFound, gin.H{"message": "Payment not found", "error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"data": payment})
}

// CreatePayment creates and processes a new payment.
// POST /payments
func (h *PaymentHandler) CreatePayment(c *gin.Context) {
	var req struct {
		SagaID     string  `json:"saga_id"     binding:"required"`
		OrderID    string  `json:"order_id"    binding:"required"`
		CustomerID string  `json:"customer_id" binding:"required"`
		Amount     float64 `json:"amount"      binding:"required,gt=0"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"message": "Validation failed",
			"error":   err.Error(),
		})
		return
	}

	payment, err := h.svc.ProcessPayment(
		c.Request.Context(),
		req.SagaID,
		req.OrderID,
		req.CustomerID,
		req.Amount,
	)
	if err != nil {
		log.Printf("[Handler] CreatePayment error: %v", err)
		if payment != nil {
			c.JSON(http.StatusPaymentRequired, gin.H{
				"message": "Payment failed",
				"data":    payment,
				"error":   err.Error(),
			})
			return
		}
		c.JSON(http.StatusInternalServerError, gin.H{"message": "Internal error", "error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"message": "Payment processed successfully",
		"data":    payment,
	})
}

// RefundPayment refunds a payment for the given saga.
// POST /payments/:id/refund
func (h *PaymentHandler) RefundPayment(c *gin.Context) {
	sagaID := c.Param("id")

	var req struct {
		OrderID string `json:"order_id" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"message": "Validation failed",
			"error":   err.Error(),
		})
		return
	}

	if err := h.svc.RefundPayment(c.Request.Context(), sagaID, req.OrderID); err != nil {
		log.Printf("[Handler] RefundPayment error: %v", err)
		c.JSON(http.StatusBadRequest, gin.H{"message": "Refund failed", "error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "Payment refunded successfully"})
}
