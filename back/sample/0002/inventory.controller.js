// src/controllers/inventory.controller.js
'use strict';

const Inventory = require('../models/inventory.model');
const logger = require('../config/logger');

/**
 * InventoryController
 *
 * REST endpoints for Service B (Inventory Service / Node.js / MongoDB).
 *
 * Endpoints
 * ─────────────────────────────────────────────────────────────────
 * GET  /inventory                  list (filter by sku, productName, status)
 * POST /inventory                  create a stock record
 * GET  /inventory/:id              single record
 * PUT  /inventory/:id              update stock
 * DEL  /inventory/:id              soft-delete
 * POST /inventory/batch            bulk-fetch by list of SKUs (called by Service A)
 * PUT  /inventory/by-sku/:sku      update by SKU (used by product-name filter)
 */

// ── List ──────────────────────────────────────────────────────────────────────
async function index(req, res) {
  try {
    const { sku, productName, status, warehouseId, page = 1, limit = 20 } = req.query;
    const filter = {};

    if (sku) filter.sku = sku;
    if (productName) filter.productName = { $regex: productName, $options: 'i' };
    if (status) filter.status = status;
    if (warehouseId) filter.warehouseId = warehouseId;

    const skip = (Number(page) - 1) * Number(limit);

    const [items, total] = await Promise.all([
      Inventory.find(filter).skip(skip).limit(Number(limit)).sort({ createdAt: -1 }),
      Inventory.countDocuments(filter),
    ]);

    return res.json({
      success: true,
      data: items,
      pagination: { page: Number(page), limit: Number(limit), total },
    });
  } catch (err) {
    logger.error('[Controller] index error', { error: err.message });
    return res.status(500).json({ success: false, message: err.message });
  }
}

// ── Create ────────────────────────────────────────────────────────────────────
async function create(req, res) {
  try {
    const { sku, productName, quantity, warehouseId, location, reservedQty } = req.body;

    if (!sku || !productName || !warehouseId) {
      return res.status(422).json({ success: false, message: 'sku, productName, warehouseId are required' });
    }

    const inventory = new Inventory({ sku, productName, quantity: quantity ?? 0, warehouseId, location, reservedQty });
    await inventory.save();

    logger.info('[Controller] Inventory created', { id: inventory._id });
    return res.status(201).json({ success: true, data: inventory });
  } catch (err) {
    logger.error('[Controller] create error', { error: err.message });
    return res.status(500).json({ success: false, message: err.message });
  }
}

// ── Show ──────────────────────────────────────────────────────────────────────
async function show(req, res) {
  try {
    const inventory = await Inventory.findById(req.params.id);
    if (!inventory) return res.status(404).json({ success: false, message: 'Inventory record not found' });

    return res.json({ success: true, data: inventory });
  } catch (err) {
    return res.status(500).json({ success: false, message: err.message });
  }
}

// ── Update ────────────────────────────────────────────────────────────────────
async function update(req, res) {
  try {
    const inventory = await Inventory.findById(req.params.id);
    if (!inventory) return res.status(404).json({ success: false, message: 'Inventory record not found' });

    const allowed = ['quantity', 'warehouseId', 'location', 'reservedQty'];
    allowed.forEach((field) => {
      if (req.body[field] !== undefined) inventory[field] = req.body[field];
    });

    await inventory.save(); // triggers pre-save status compute

    logger.info('[Controller] Inventory updated', { id: inventory._id });
    return res.json({ success: true, data: inventory });
  } catch (err) {
    return res.status(500).json({ success: false, message: err.message });
  }
}

// ── Delete (soft) ─────────────────────────────────────────────────────────────
async function destroy(req, res) {
  try {
    const inventory = await Inventory.findById(req.params.id);
    if (!inventory) return res.status(404).json({ success: false, message: 'Inventory record not found' });

    await inventory.softDelete();

    logger.info('[Controller] Inventory soft-deleted', { id: inventory._id });
    return res.json({ success: true, message: 'Inventory record deleted' });
  } catch (err) {
    return res.status(500).json({ success: false, message: err.message });
  }
}

// ── Batch (called by Product Service) ────────────────────────────────────────
async function batch(req, res) {
  try {
    const { skus } = req.body;
    if (!Array.isArray(skus) || skus.length === 0) {
      return res.status(422).json({ success: false, message: '`skus` array is required' });
    }

    const items = await Inventory.find({ sku: { $in: skus } });
    return res.json({ success: true, data: items });
  } catch (err) {
    return res.status(500).json({ success: false, message: err.message });
  }
}

// ── Update by SKU (cross-service helper) ─────────────────────────────────────
async function updateBySku(req, res) {
  try {
    const { sku } = req.params;
    const updates = {};
    const allowed = ['quantity', 'warehouseId', 'location', 'reservedQty'];
    allowed.forEach((f) => { if (req.body[f] !== undefined) updates[f] = req.body[f]; });

    const result = await Inventory.updateMany({ sku }, { $set: updates });

    if (result.matchedCount === 0) {
      return res.status(404).json({ success: false, message: `No inventory records found for SKU: ${sku}` });
    }

    const updated = await Inventory.find({ sku });
    return res.json({ success: true, data: updated });
  } catch (err) {
    return res.status(500).json({ success: false, message: err.message });
  }
}

module.exports = { index, create, show, update, destroy, batch, updateBySku };
