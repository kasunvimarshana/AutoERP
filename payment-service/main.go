package main

import (
	"context"
	"fmt"
	"log"
	"os"

	"github.com/gin-gonic/gin"
	"github.com/saga/payment-service/internal/handler"
	"github.com/saga/payment-service/internal/messaging"
	"github.com/saga/payment-service/internal/service"
	"github.com/saga/payment-service/internal/store"
)

func main() {
	// ── Redis ────────────────────────────────────────────────────────────────
	redisHost := getEnv("REDIS_HOST", "redis")
	redisPort := getEnv("REDIS_PORT", "6379")
	redisAddr := fmt.Sprintf("%s:%s", redisHost, redisPort)

	redisStore, err := store.NewRedisStore(redisAddr)
	if err != nil {
		log.Fatalf("failed to connect to Redis: %v", err)
	}
	log.Printf("Connected to Redis at %s", redisAddr)

	// ── RabbitMQ Publisher ───────────────────────────────────────────────────
	rabbitmqURL := getEnv("RABBITMQ_URL", "amqp://guest:guest@rabbitmq:5672/")

	publisher, err := messaging.NewPublisher(rabbitmqURL)
	if err != nil {
		log.Fatalf("failed to connect RabbitMQ publisher: %v", err)
	}
	defer publisher.Close()
	log.Printf("RabbitMQ publisher connected to %s", rabbitmqURL)

	// ── Payment Service ──────────────────────────────────────────────────────
	paymentSvc := service.NewPaymentService(redisStore, publisher)

	// ── RabbitMQ Consumer ────────────────────────────────────────────────────
	consumer, err := messaging.NewConsumer(rabbitmqURL, paymentSvc, publisher)
	if err != nil {
		log.Fatalf("failed to connect RabbitMQ consumer: %v", err)
	}
	defer consumer.Close()

	// Start consuming in background goroutine
	go func() {
		log.Printf("Starting RabbitMQ consumer...")
		if err := consumer.Start(context.Background()); err != nil {
			log.Printf("RabbitMQ consumer error: %v", err)
		}
	}()

	// ── HTTP Server ──────────────────────────────────────────────────────────
	ginMode := getEnv("GIN_MODE", "debug")
	gin.SetMode(ginMode)

	r := gin.New()
	r.Use(gin.Logger())
	r.Use(gin.Recovery())

	h := handler.NewPaymentHandler(paymentSvc)

	r.GET("/health", h.HealthCheck)
	r.GET("/payments/:id", h.GetPayment)
	r.POST("/payments", h.CreatePayment)
	r.POST("/payments/:id/refund", h.RefundPayment)

	port := getEnv("PORT", "8002")
	log.Printf("Payment service starting on :%s", port)

	if err := r.Run(":" + port); err != nil {
		log.Fatalf("failed to start HTTP server: %v", err)
	}
}

func getEnv(key, defaultVal string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return defaultVal
}
