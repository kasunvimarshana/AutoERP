package messaging

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"time"

	amqp "github.com/rabbitmq/amqp091-go"
)

// Publisher sends messages to RabbitMQ.
type Publisher struct {
	conn    *amqp.Connection
	channel *amqp.Channel
}

// NewPublisher creates a Publisher connected to RabbitMQ.
func NewPublisher(amqpURL string) (*Publisher, error) {
	conn, err := connectWithRetry(amqpURL, 5)
	if err != nil {
		return nil, fmt.Errorf("publisher connect: %w", err)
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

	return &Publisher{conn: conn, channel: ch}, nil
}

// PublishReply publishes a reply message to the saga.replies exchange.
func (p *Publisher) PublishReply(ctx context.Context, exchange, routingKey string, message interface{}) error {
	body, err := json.Marshal(message)
	if err != nil {
		return fmt.Errorf("marshal message: %w", err)
	}

	err = p.channel.PublishWithContext(
		ctx,
		exchange,
		routingKey,
		false, // mandatory
		false, // immediate
		amqp.Publishing{
			ContentType:  "application/json",
			DeliveryMode: amqp.Persistent,
			Timestamp:    time.Now(),
			Body:         body,
		},
	)
	if err != nil {
		return fmt.Errorf("publish: %w", err)
	}

	log.Printf("[Publisher] Published to exchange=%s routing_key=%s", exchange, routingKey)
	return nil
}

// Close closes the channel and connection.
func (p *Publisher) Close() {
	if p.channel != nil {
		p.channel.Close()
	}
	if p.conn != nil {
		p.conn.Close()
	}
}
