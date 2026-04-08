"""
analytics-service/consumer.py
──────────────────────────────
Example Service C – Analytics Service (Python / PostgreSQL)

Demonstrates how ANY language/stack subscribes to product events
from the same RabbitMQ fanout exchange.

Requirements:
    pip install pika psycopg2-binary python-dotenv
"""

import json
import logging
import os
import time

import pika
import psycopg2
from dotenv import load_dotenv

load_dotenv()

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
logger = logging.getLogger(__name__)

# ── PostgreSQL ────────────────────────────────────────────────────────────────

def get_pg_connection():
    return psycopg2.connect(
        host=os.getenv("PG_HOST", "localhost"),
        port=os.getenv("PG_PORT", 5432),
        dbname=os.getenv("PG_DB", "analytics_db"),
        user=os.getenv("PG_USER", "postgres"),
        password=os.getenv("PG_PASSWORD", "postgres"),
    )


def ensure_table(conn):
    with conn.cursor() as cur:
        cur.execute("""
            CREATE TABLE IF NOT EXISTS product_events_log (
                id          SERIAL PRIMARY KEY,
                event_type  VARCHAR(100) NOT NULL,
                sku         VARCHAR(100) NOT NULL,
                product_id  INTEGER,
                payload     JSONB        NOT NULL,
                occurred_at TIMESTAMPTZ  NOT NULL,
                created_at  TIMESTAMPTZ  DEFAULT NOW()
            );
        """)
        conn.commit()
    logger.info("[DB] Table product_events_log ready")


# ── Event Handlers ────────────────────────────────────────────────────────────

def on_product_created(payload: dict, conn):
    with conn.cursor() as cur:
        cur.execute(
            """
            INSERT INTO product_events_log (event_type, sku, product_id, payload, occurred_at)
            VALUES (%s, %s, %s, %s, NOW())
            ON CONFLICT DO NOTHING
            """,
            ("product.created", payload["sku"], payload["id"], json.dumps(payload)),
        )
    conn.commit()
    logger.info(f"[Handler] Logged product.created: {payload['sku']}")


def on_product_updated(payload: dict, conn):
    current = payload.get("current", {})
    with conn.cursor() as cur:
        cur.execute(
            """
            INSERT INTO product_events_log (event_type, sku, product_id, payload, occurred_at)
            VALUES (%s, %s, %s, %s, NOW())
            """,
            ("product.updated", current.get("sku"), current.get("id"), json.dumps(payload)),
        )
    conn.commit()
    logger.info(f"[Handler] Logged product.updated: {current.get('sku')}")


def on_product_deleted(payload: dict, conn):
    with conn.cursor() as cur:
        cur.execute(
            """
            INSERT INTO product_events_log (event_type, sku, product_id, payload, occurred_at)
            VALUES (%s, %s, %s, %s, NOW())
            """,
            ("product.deleted", payload["sku"], payload["id"], json.dumps(payload)),
        )
    conn.commit()
    logger.info(f"[Handler] Logged product.deleted: {payload['sku']}")


# ── RabbitMQ Consumer ─────────────────────────────────────────────────────────

def make_callback(pg_conn):
    def callback(ch, method, _properties, body):
        try:
            envelope = json.loads(body)
            event_type = envelope.get("event_type")
            payload = envelope.get("payload", {})

            logger.info(f"[Consumer] Received: {event_type}")

            if event_type == "product.created":
                on_product_created(payload, pg_conn)
            elif event_type == "product.updated":
                on_product_updated(payload, pg_conn)
            elif event_type == "product.deleted":
                on_product_deleted(payload, pg_conn)
            else:
                logger.warning(f"[Consumer] Unknown event: {event_type}")

            ch.basic_ack(delivery_tag=method.delivery_tag)

        except Exception as exc:
            logger.error(f"[Consumer] Handler error: {exc}")
            # nack → dead-letter queue
            ch.basic_nack(delivery_tag=method.delivery_tag, requeue=False)

    return callback


def connect_rabbitmq(retries=10, delay=3):
    for attempt in range(1, retries + 1):
        try:
            params = pika.URLParameters(os.getenv("RABBITMQ_URL", "amqp://guest:guest@localhost:5672"))
            return pika.BlockingConnection(params)
        except Exception as exc:
            logger.warning(f"[RabbitMQ] Attempt {attempt}/{retries}: {exc}")
            if attempt == retries:
                raise
            time.sleep(delay)


def main():
    exchange = os.getenv("RABBITMQ_EXCHANGE", "product_events")
    queue_name = "analytics_service_queue"

    # PostgreSQL
    pg_conn = get_pg_connection()
    ensure_table(pg_conn)

    # RabbitMQ
    mq_conn = connect_rabbitmq()
    channel = mq_conn.channel()

    channel.exchange_declare(exchange, exchange_type="fanout", durable=True)

    channel.queue_declare(queue_name, durable=True)
    channel.queue_bind(queue_name, exchange, routing_key="")

    channel.basic_qos(prefetch_count=1)
    channel.basic_consume(queue_name, on_message_callback=make_callback(pg_conn))

    logger.info(f"[Consumer] Analytics Service listening on queue: {queue_name}")
    channel.start_consuming()


if __name__ == "__main__":
    main()
