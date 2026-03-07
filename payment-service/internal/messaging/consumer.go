package messaging

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"time"

	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/saga/payment-service/internal/service"
)

const (
	exchangeCommands = "saga.commands"
	exchangeReplies  = "saga.replies"
	queueProcessPay  = "process-payment"
	queueRefundPay   = "refund-payment"
	queueReplies     = "saga-replies"
)

// replyMessage is the structure sent back to the saga orchestrator.
type replyMessage struct {
	SagaID    string                 `json:"saga_id"`
	OrderID   string                 `json:"order_id"`
	Type      string                 `json:"type"`
	Success   bool                   `json:"success"`
	Data      map[string]interface{} `json:"data"`
	Error     string                 `json:"error"`
	Timestamp string                 `json:"timestamp"`
}

// commandMessage is the structure received from the orchestrator.
type commandMessage struct {
	SagaID  string                 `json:"saga_id"`
	OrderID string                 `json:"order_id"`
	Type    string                 `json:"type"`
	Payload map[string]interface{} `json:"payload"`
}

// Consumer listens for payment commands from RabbitMQ.
type Consumer struct {
	conn    *amqp.Connection
	channel *amqp.Channel
	svc     *service.PaymentService
	pub     *Publisher
}

// NewConsumer creates a Consumer connected to RabbitMQ.
func NewConsumer(amqpURL string, svc *service.PaymentService, pub *Publisher) (*Consumer, error) {
	conn, err := connectWithRetry(amqpURL, 5)
	if err != nil {
		return nil, fmt.Errorf("consumer connect: %w", err)
	}

	ch, err := conn.Channel()
	if err != nil {
		conn.Close()
		return nil, fmt.Errorf("open channel: %w", err)
	}

	if err := declareTopology(ch); err != nil {
		ch.Close()
		conn.Close()
		return nil, fmt.Errorf("declare topology: %w", err)
	}

	if err := ch.Qos(1, 0, false); err != nil {
		ch.Close()
		conn.Close()
		return nil, fmt.Errorf("set qos: %w", err)
	}

	return &Consumer{conn: conn, channel: ch, svc: svc, pub: pub}, nil
}

// Start begins consuming from both payment queues.
func (c *Consumer) Start(ctx context.Context) error {
	processMsgs, err := c.channel.Consume(queueProcessPay, "", false, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("consume %s: %w", queueProcessPay, err)
	}

	refundMsgs, err := c.channel.Consume(queueRefundPay, "", false, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("consume %s: %w", queueRefundPay, err)
	}

	log.Printf("[Consumer] Listening on queues: %s, %s", queueProcessPay, queueRefundPay)

	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case msg, ok := <-processMsgs:
			if !ok {
				return fmt.Errorf("process-payment channel closed")
			}
			c.handleProcessPayment(ctx, msg)
		case msg, ok := <-refundMsgs:
			if !ok {
				return fmt.Errorf("refund-payment channel closed")
			}
			c.handleRefundPayment(ctx, msg)
		}
	}
}

func (c *Consumer) handleProcessPayment(ctx context.Context, msg amqp.Delivery) {
	var cmd commandMessage
	if err := json.Unmarshal(msg.Body, &cmd); err != nil {
		log.Printf("[Consumer] Failed to unmarshal process-payment message: %v", err)
		msg.Nack(false, false)
		return
	}

	log.Printf("[Consumer] Processing payment command: saga=%s order=%s", cmd.SagaID, cmd.OrderID)

	amount, _     := cmd.Payload["amount"].(float64)
	customerID, _ := cmd.Payload["customer_id"].(string)

	payment, err := c.svc.ProcessPayment(ctx, cmd.SagaID, cmd.OrderID, customerID, amount)

	var reply replyMessage
	if err != nil {
		reply = replyMessage{
			SagaID:    cmd.SagaID,
			OrderID:   cmd.OrderID,
			Type:      "PAYMENT_FAILED",
			Success:   false,
			Data:      map[string]interface{}{},
			Error:     err.Error(),
			Timestamp: time.Now().UTC().Format(time.RFC3339),
		}
		log.Printf("[Consumer] Payment failed: saga=%s error=%v", cmd.SagaID, err)
	} else {
		reply = replyMessage{
			SagaID:  cmd.SagaID,
			OrderID: cmd.OrderID,
			Type:    "PAYMENT_PROCESSED",
			Success: true,
			Data: map[string]interface{}{
				"payment_id":     payment.ID,
				"transaction_id": payment.TransactionID,
				"amount":         payment.Amount,
				"status":         payment.Status,
			},
			Error:     "",
			Timestamp: time.Now().UTC().Format(time.RFC3339),
		}
		log.Printf("[Consumer] Payment processed: saga=%s txn=%s", cmd.SagaID, payment.TransactionID)
	}

	if pubErr := c.pub.PublishReply(ctx, exchangeReplies, queueReplies, reply); pubErr != nil {
		log.Printf("[Consumer] Failed to publish reply: %v", pubErr)
		msg.Nack(false, true) // requeue
		return
	}

	msg.Ack(false)
}

func (c *Consumer) handleRefundPayment(ctx context.Context, msg amqp.Delivery) {
	var cmd commandMessage
	if err := json.Unmarshal(msg.Body, &cmd); err != nil {
		log.Printf("[Consumer] Failed to unmarshal refund-payment message: %v", err)
		msg.Nack(false, false)
		return
	}

	log.Printf("[Consumer] Processing refund command: saga=%s order=%s", cmd.SagaID, cmd.OrderID)

	err := c.svc.RefundPayment(ctx, cmd.SagaID, cmd.OrderID)

	reply := replyMessage{
		SagaID:    cmd.SagaID,
		OrderID:   cmd.OrderID,
		Type:      "PAYMENT_REFUNDED",
		Success:   err == nil,
		Data:      map[string]interface{}{},
		Error:     "",
		Timestamp: time.Now().UTC().Format(time.RFC3339),
	}

	if err != nil {
		reply.Error = err.Error()
		log.Printf("[Consumer] Refund failed: saga=%s error=%v", cmd.SagaID, err)
	} else {
		log.Printf("[Consumer] Refund processed: saga=%s", cmd.SagaID)
	}

	if pubErr := c.pub.PublishReply(ctx, exchangeReplies, queueReplies, reply); pubErr != nil {
		log.Printf("[Consumer] Failed to publish refund reply: %v", pubErr)
		msg.Nack(false, true)
		return
	}

	msg.Ack(false)
}

// Close closes channel and connection.
func (c *Consumer) Close() {
	if c.channel != nil {
		c.channel.Close()
	}
	if c.conn != nil {
		c.conn.Close()
	}
}

// declareTopology creates all exchanges and queues needed by the payment service.
func declareTopology(ch *amqp.Channel) error {
	exchanges := []string{exchangeCommands, exchangeReplies}
	for _, ex := range exchanges {
		if err := ch.ExchangeDeclare(ex, "direct", true, false, false, false, nil); err != nil {
			return fmt.Errorf("declare exchange %s: %w", ex, err)
		}
	}

	queues := []struct {
		name     string
		exchange string
	}{
		{"reserve-inventory", exchangeCommands},
		{"release-inventory", exchangeCommands},
		{"process-payment", exchangeCommands},
		{"refund-payment", exchangeCommands},
		{"send-notification", exchangeCommands},
		{"saga-replies", exchangeReplies},
	}

	for _, q := range queues {
		if _, err := ch.QueueDeclare(q.name, true, false, false, false, nil); err != nil {
			return fmt.Errorf("declare queue %s: %w", q.name, err)
		}
		if err := ch.QueueBind(q.name, q.name, q.exchange, false, nil); err != nil {
			return fmt.Errorf("bind queue %s: %w", q.name, err)
		}
	}

	return nil
}

// connectWithRetry attempts to connect to RabbitMQ with exponential backoff.
func connectWithRetry(amqpURL string, maxAttempts int) (*amqp.Connection, error) {
	var conn *amqp.Connection
	var err error

	for i := 1; i <= maxAttempts; i++ {
		conn, err = amqp.Dial(amqpURL)
		if err == nil {
			log.Printf("[RabbitMQ] Connected on attempt %d", i)
			return conn, nil
		}
		log.Printf("[RabbitMQ] Connect attempt %d/%d failed: %v", i, maxAttempts, err)
		if i < maxAttempts {
			sleep := time.Duration(i*i) * time.Second
			log.Printf("[RabbitMQ] Retrying in %v...", sleep)
			time.Sleep(sleep)
		}
	}

	return nil, fmt.Errorf("failed after %d attempts: %w", maxAttempts, err)
}
