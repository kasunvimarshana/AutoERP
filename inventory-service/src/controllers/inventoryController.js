'use strict';

const { body, param, query, validationResult } = require('express-validator');
const Inventory = require('../models/Inventory');
const logger = require('../middleware/logger');

/**
 * InventoryController
 *
 * Handles HTTP requests for Inventory CRUD operations.
 * Supports cross-service filtering by product_name.
 *
 * Endpoints:
 *   GET    /api/v1/inventory                  List all inventory (with optional filters)
 *   POST   /api/v1/inventory                  Create inventory record
 *   GET    /api/v1/inventory/:id              Get single inventory record
 *   PUT    /api/v1/inventory/:id              Update inventory record
 *   DELETE /api/v1/inventory/:id              Delete inventory record
 *
 * Cross-service query support:
 *   GET    /api/v1/inventory?product_name=X   Filter by product name (used by Product Service)
 *   GET    /api/v1/inventory?product_id=X     Filter by product ID
 */

/**
 * GET /api/v1/inventory
 * List inventory records with optional filters.
 * Supports: product_name, product_id, warehouse_location, is_active, page, per_page
 */
const index = async (req, res) => {
  try {
    const {
      product_name,
      product_id,
      warehouse_location,
      is_active,
      page = 1,
      per_page = 15,
    } = req.query;

    const filter = {};

    if (product_name) {
      // Case-insensitive partial match for cross-service filtering
      filter.product_name = { $regex: product_name, $options: 'i' };
    }

    if (product_id) {
      filter.product_id = parseInt(product_id, 10);
    }

    if (warehouse_location) {
      filter.warehouse_location = { $regex: warehouse_location, $options: 'i' };
    }

    if (is_active !== undefined) {
      filter.is_active = is_active === 'true' || is_active === '1';
    }

    const pageNum   = Math.max(1, parseInt(page, 10));
    const perPageNum = Math.min(100, Math.max(1, parseInt(per_page, 10)));
    const skip      = (pageNum - 1) * perPageNum;

    const [items, total] = await Promise.all([
      Inventory.find(filter).skip(skip).limit(perPageNum).sort({ createdAt: -1 }),
      Inventory.countDocuments(filter),
    ]);

    return res.json({
      success: true,
      data: items,
      meta: {
        current_page: pageNum,
        per_page: perPageNum,
        total,
        last_page: Math.ceil(total / perPageNum),
      },
    });
  } catch (err) {
    logger.error('InventoryController@index error', { error: err.message });
    return res.status(500).json({
      success: false,
      message: 'Failed to retrieve inventory records.',
      error: err.message,
    });
  }
};

/**
 * POST /api/v1/inventory
 * Create a new inventory record.
 */
const store = async (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(422).json({
      success: false,
      message: 'Validation failed.',
      errors: errors.array(),
    });
  }

  try {
    const {
      product_id,
      product_name,
      product_sku,
      quantity,
      reserved_quantity,
      warehouse_location,
      reorder_threshold,
      notes,
    } = req.body;

    const inventory = new Inventory({
      product_id,
      product_name,
      product_sku,
      quantity,
      reserved_quantity,
      warehouse_location,
      reorder_threshold,
      notes,
    });

    await inventory.save();

    logger.info('InventoryController: Created inventory record', {
      id: inventory._id,
      product_id,
      product_name,
    });

    return res.status(201).json({
      success: true,
      message: 'Inventory record created successfully.',
      data: inventory,
    });
  } catch (err) {
    // Handle duplicate key (product_id + warehouse_location)
    if (err.code === 11000) {
      return res.status(409).json({
        success: false,
        message: 'An inventory record for this product at this warehouse already exists.',
        error: err.message,
      });
    }

    logger.error('InventoryController@store error', { error: err.message });
    return res.status(500).json({
      success: false,
      message: 'Failed to create inventory record.',
      error: err.message,
    });
  }
};

/**
 * GET /api/v1/inventory/:id
 * Get a single inventory record by MongoDB ID.
 */
const show = async (req, res) => {
  try {
    const inventory = await Inventory.findById(req.params.id);

    if (!inventory) {
      return res.status(404).json({
        success: false,
        message: 'Inventory record not found.',
      });
    }

    return res.json({
      success: true,
      data: inventory,
    });
  } catch (err) {
    // Handle invalid MongoDB ObjectId
    if (err.name === 'CastError') {
      return res.status(404).json({
        success: false,
        message: 'Inventory record not found.',
      });
    }

    logger.error('InventoryController@show error', { error: err.message });
    return res.status(500).json({
      success: false,
      message: 'Failed to retrieve inventory record.',
      error: err.message,
    });
  }
};

/**
 * PUT /api/v1/inventory/:id
 * Update an inventory record by MongoDB ID.
 */
const update = async (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(422).json({
      success: false,
      message: 'Validation failed.',
      errors: errors.array(),
    });
  }

  try {
    const allowedFields = [
      'product_name',
      'product_sku',
      'quantity',
      'reserved_quantity',
      'warehouse_location',
      'reorder_threshold',
      'notes',
      'is_active',
    ];

    const updateData = {};
    allowedFields.forEach((field) => {
      if (req.body[field] !== undefined) {
        updateData[field] = req.body[field];
      }
    });

    const inventory = await Inventory.findByIdAndUpdate(
      req.params.id,
      { $set: updateData },
      { new: true, runValidators: true }
    );

    if (!inventory) {
      return res.status(404).json({
        success: false,
        message: 'Inventory record not found.',
      });
    }

    logger.info('InventoryController: Updated inventory record', {
      id: inventory._id,
      product_name: inventory.product_name,
    });

    return res.json({
      success: true,
      message: 'Inventory record updated successfully.',
      data: inventory,
    });
  } catch (err) {
    if (err.name === 'CastError') {
      return res.status(404).json({
        success: false,
        message: 'Inventory record not found.',
      });
    }

    logger.error('InventoryController@update error', { error: err.message });
    return res.status(500).json({
      success: false,
      message: 'Failed to update inventory record.',
      error: err.message,
    });
  }
};

/**
 * DELETE /api/v1/inventory/:id
 * Delete a single inventory record by MongoDB ID.
 */
const destroy = async (req, res) => {
  try {
    const inventory = await Inventory.findByIdAndDelete(req.params.id);

    if (!inventory) {
      return res.status(404).json({
        success: false,
        message: 'Inventory record not found.',
      });
    }

    logger.info('InventoryController: Deleted inventory record', {
      id: req.params.id,
      product_name: inventory.product_name,
    });

    return res.json({
      success: true,
      message: 'Inventory record deleted successfully.',
    });
  } catch (err) {
    if (err.name === 'CastError') {
      return res.status(404).json({
        success: false,
        message: 'Inventory record not found.',
      });
    }

    logger.error('InventoryController@destroy error', { error: err.message });
    return res.status(500).json({
      success: false,
      message: 'Failed to delete inventory record.',
      error: err.message,
    });
  }
};

/**
 * Validation rules for creating an inventory record.
 */
const storeValidationRules = [
  body('product_id')
    .isInt({ min: 1 })
    .withMessage('product_id must be a positive integer'),
  body('product_name')
    .notEmpty()
    .withMessage('product_name is required')
    .isString()
    .trim(),
  body('quantity')
    .isInt({ min: 0 })
    .withMessage('quantity must be a non-negative integer'),
  body('reserved_quantity')
    .optional()
    .isInt({ min: 0 })
    .withMessage('reserved_quantity must be a non-negative integer'),
  body('reorder_threshold')
    .optional()
    .isInt({ min: 0 })
    .withMessage('reorder_threshold must be a non-negative integer'),
];

/**
 * Validation rules for updating an inventory record.
 */
const updateValidationRules = [
  body('quantity')
    .optional()
    .isInt({ min: 0 })
    .withMessage('quantity must be a non-negative integer'),
  body('reserved_quantity')
    .optional()
    .isInt({ min: 0 })
    .withMessage('reserved_quantity must be a non-negative integer'),
  body('reorder_threshold')
    .optional()
    .isInt({ min: 0 })
    .withMessage('reorder_threshold must be a non-negative integer'),
];

module.exports = {
  index,
  store,
  show,
  update,
  destroy,
  storeValidationRules,
  updateValidationRules,
};
