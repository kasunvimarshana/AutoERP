"""
Inventory Service – FastAPI + MongoDB
Manages product inventory; supports reserve/release as part of the Order Saga.
"""
from __future__ import annotations

import os
import structlog
from contextlib import asynccontextmanager
from typing import Optional

from fastapi import FastAPI, HTTPException, Header
from motor.motor_asyncio import AsyncIOMotorClient
from pydantic import BaseModel, Field
from datetime import datetime, timezone

# ─── Logging ─────────────────────────────────────────────────────────────────
structlog.configure(
    processors=[
        structlog.processors.TimeStamper(fmt="iso"),
        structlog.processors.add_log_level,
        structlog.processors.JSONRenderer(),
    ]
)
log = structlog.get_logger()

# ─── Config ──────────────────────────────────────────────────────────────────
MONGODB_URL = os.getenv("MONGODB_URL", "mongodb://mongodb:27017")
MONGODB_DB  = os.getenv("MONGODB_DB", "inventory_db")

# ─── MongoDB Client ──────────────────────────────────────────────────────────
mongo_client: Optional[AsyncIOMotorClient] = None
db = None


@asynccontextmanager
async def lifespan(application: FastAPI):
    global mongo_client, db
    mongo_client = AsyncIOMotorClient(MONGODB_URL)
    db = mongo_client[MONGODB_DB]
    await _seed_inventory()
    log.info("inventory_service_started", db=MONGODB_DB)
    yield
    mongo_client.close()
    log.info("inventory_service_stopped")


app = FastAPI(title="Inventory Service", version="1.0.0", lifespan=lifespan)


# ─── Pydantic Models ─────────────────────────────────────────────────────────
class InventoryItem(BaseModel):
    sku: str
    quantity: int = Field(ge=1)
    price: float = Field(ge=0)


class ReserveRequest(BaseModel):
    order_id: str
    saga_id: str
    tenant_id: str
    items: list[InventoryItem]


class ReleaseRequest(BaseModel):
    order_id: str
    saga_id: str
    tenant_id: str
    reason: Optional[str] = "unspecified"


class UpdateStockRequest(BaseModel):
    delta: int  # positive = add stock, negative = subtract stock


# ─── Helpers ─────────────────────────────────────────────────────────────────
async def _seed_inventory():
    """Seed demo products if the collection is empty."""
    count = await db.products.count_documents({})
    if count == 0:
        await db.products.insert_many([
            {"sku": "LAPTOP-001",  "name": "Laptop Pro 16",   "quantity": 50,  "price": 1499.99},
            {"sku": "PHONE-001",   "name": "Smartphone X12",  "quantity": 200, "price": 799.99},
            {"sku": "TABLET-001",  "name": "Tablet Air 11",   "quantity": 100, "price": 499.99},
            {"sku": "MONITOR-001", "name": "4K Monitor 27\"", "quantity": 30,  "price": 349.99},
            {"sku": "KEYBOARD-01", "name": "Mechanical KB",   "quantity": 150, "price": 89.99},
        ])
        log.info("inventory_seeded", count=5)


async def _rollback_reservations(reserved_items: list[dict], order_id: str):
    """Re-add quantities back to products (undo reservation)."""
    for item in reserved_items:
        await db.products.update_one(
            {"sku": item["sku"]},
            {"$inc": {"quantity": item["quantity"]}},
        )
        log.info(
            "reservation_rolled_back",
            sku=item["sku"],
            quantity=item["quantity"],
            order_id=order_id,
        )


# ─── Routes ──────────────────────────────────────────────────────────────────
@app.get("/health")
async def health():
    return {
        "service":   "inventory-service",
        "status":    "healthy",
        "timestamp": datetime.now(timezone.utc).isoformat(),
    }


@app.get("/api/inventory")
async def list_inventory(
    x_tenant_id: Optional[str] = Header(default=None),
    x_correlation_id: Optional[str] = Header(default=None),
):
    products = await db.products.find({}, {"_id": 0}).to_list(length=100)
    return {"items": products, "count": len(products)}


@app.get("/api/inventory/{sku}")
async def get_product(sku: str):
    product = await db.products.find_one({"sku": sku}, {"_id": 0})
    if not product:
        raise HTTPException(status_code=404, detail=f"SKU {sku!r} not found.")
    return product


@app.post("/api/inventory/reserve")
async def reserve_inventory(body: ReserveRequest):
    """
    Saga Step 2 – Reserve inventory for an order.
    Uses optimistic locking: decrements stock only if sufficient.
    Rolls back partial reservations on failure.
    """
    log.info("inventory_reserve_start", order_id=body.order_id, saga_id=body.saga_id)

    reserved: list[dict] = []

    for item in body.items:
        product = await db.products.find_one({"sku": item.sku})
        if not product:
            await _rollback_reservations(reserved, body.order_id)
            raise HTTPException(status_code=404, detail=f"SKU {item.sku!r} not found.")

        if product["quantity"] < item.quantity:
            log.warning(
                "insufficient_stock",
                sku=item.sku,
                available=product["quantity"],
                requested=item.quantity,
            )
            await _rollback_reservations(reserved, body.order_id)
            raise HTTPException(
                status_code=409,
                detail=(
                    f"Insufficient stock for SKU {item.sku!r}. "
                    f"Available: {product['quantity']}, requested: {item.quantity}."
                ),
            )

        await db.products.update_one(
            {"sku": item.sku, "quantity": {"$gte": item.quantity}},
            {"$inc": {"quantity": -item.quantity}},
        )
        reserved.append({"sku": item.sku, "quantity": item.quantity})
        log.info("sku_reserved", sku=item.sku, quantity=item.quantity)

    # Persist reservation record for audit/compensation
    await db.reservations.insert_one({
        "order_id":   body.order_id,
        "saga_id":    body.saga_id,
        "tenant_id":  body.tenant_id,
        "items":      reserved,
        "status":     "reserved",
        "created_at": datetime.now(timezone.utc).isoformat(),
    })

    log.info("inventory_reserve_success", order_id=body.order_id)
    return {"status": "reserved", "order_id": body.order_id, "items": reserved}


@app.post("/api/inventory/release")
async def release_inventory(body: ReleaseRequest):
    """
    Saga Compensation Step – Release reserved inventory (rollback).
    Called when payment fails or order is cancelled.
    """
    log.info("inventory_release_start", order_id=body.order_id, reason=body.reason)

    reservation = await db.reservations.find_one(
        {"order_id": body.order_id, "status": "reserved"}
    )
    if not reservation:
        log.warning("reservation_not_found", order_id=body.order_id)
        return {"status": "not_found", "order_id": body.order_id}

    await _rollback_reservations(reservation["items"], body.order_id)

    await db.reservations.update_one(
        {"order_id": body.order_id},
        {
            "$set": {
                "status":      "released",
                "reason":      body.reason,
                "released_at": datetime.now(timezone.utc).isoformat(),
            }
        },
    )

    log.info("inventory_release_success", order_id=body.order_id)
    return {"status": "released", "order_id": body.order_id}


@app.patch("/api/inventory/{sku}/stock")
async def update_stock(sku: str, body: UpdateStockRequest):
    """Manually adjust stock level (admin operation)."""
    result = await db.products.update_one(
        {"sku": sku},
        {"$inc": {"quantity": body.delta}},
    )
    if result.matched_count == 0:
        raise HTTPException(status_code=404, detail=f"SKU {sku!r} not found.")
    product = await db.products.find_one({"sku": sku}, {"_id": 0})
    return product
